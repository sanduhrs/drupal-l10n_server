/* $Id$ */

/**
 * @file
 *   Translation editor behaviors.
 */

(function($) {

  /**
   * Simple string encoding/escaping for proper HTML output.
   */
  encode = function(str) {
    str = String(str);
    var replace = { '&': '&amp;', '<': '&lt;', '>': '&gt;' };
    for (var character in replace) {
      var regex = new RegExp(character, 'g');
      str = str.replace(regex, replace[character]);
    }
    return str;
  };

  // Add behaviors to placeholders so that they highlight the corrsponding
  // placeholder(s) with the same name on the same table row.
  $('em.l10n-placeholder')
    .live('mouseover', function() {
      $(this).closest('tr').find('.l10n-placeholder:contains("' + $(this).text() + '")').addClass('highlight');
    })
    .live('mouseout', function() {
      $('.l10n-placeholder.highlight').removeClass('highlight');
    });

  $(function () {
    
    // Replace "More information" link with AJAX output.
    $('.l10n-more').click(function() {
      $(this)
        .addClass('loading')
        .parent().load(this.href);
      return false;
    });

    var markup = function(string) {
      // Highlight placeholders with the l10n-placeholder class.
      string = string.replace(/([!@%]|<(ins|del)>[!@%]<\/(ins|del)>)(\w+|<(ins|del)>\w+<\/(ins|del)>)/g, '<em class="l10n-placeholder">$&</em>');

      // Wrap HTML tags in <code> tags.
      string = string.replace(/(&lt;.+?(&gt;|$))/g, function(str) {
        return '<code>' + str.replace(/<[^>]+>/g, '</code>$&<code>') + '</code>';
      });

      string = string.replace(/\\[^<]/g, '<span class="l10n-escape">$&</span>');

      // Add markers for newlines.
      string = string.replace(/\n/g, '<span class="l10n-nl"></span>$&');
      
      return string;
    };

    $('td.translation').parent().each(function() {
      var all = $('li.translation', this);
      var strings = all.find('.l10n-string > span');
      var source = $('td.source', this);

      // Add special tags to the source markup cells.
      source.find('.l10n-string span').each(function() {
        $(this).html(markup($(this).html()));
      });

      // Initialize data for the worddiff tool.
      strings.each(function() {
        var orig = $(this).html(), markedUp = markup(orig);
        $(this)
          .html(markedUp)
          .data('worddiff:original', orig)
          .data('worddiff:markup', markedUp);
      });

      // Method to set status classes based on associated checkbox value.
      var setStatus = function(elem, status, value) {
        newValue = elem.find('.' + status + ' :checkbox').attr('checked', value).attr('checked');
        elem[(newValue === undefined ? value : newValue) ? 'addClass' : 'removeClass']('is-' + status);
      };

      var textareas = all.filter('.new-translation').find('textarea');

      // Callback for when the edit button was pressed.
      $(this).find('ul.actions .edit').click(function() {
        var translation = $(this).closest('td.source, li.translation');
        var confirmed = undefined;
        textareas.each(function(i) {
          var textarea = $(this);
          var val = textarea.val();
          if (confirmed || val === textarea.attr('defaultValue') || !val || (confirmed === undefined && (confirmed = confirm(Drupal.t("Do you want to overwrite the current suggestion?"))))) {
            // If not the default value, and still editing that means there was something
            // added into the field without it being saved first, and is being edited again.
            textarea.val(translation.find('.l10n-string > span:eq('+ i +')').text()).keyup();
            if (i == 0) {
              // Since we can't have multiple focuses, we jut focus the first textarea.
              textarea.focus();
            }
          }
        });
      });

      all.each(function() {
        var translation = $(this);
        var isTranslation = !translation.is('.no-translation');
        var siblings = all.not(this).not('.no-translation');

        var removeDiff = function() {
          strings.worddiffRevert();
        };

        var updateDiff = function() {
          removeDiff();
          if (isTranslation) {
            var orig = siblings.filter('.is-active');
            if (!orig.length) {
              orig = siblings.filter('.default');
            }
            if (!orig.length) {
              orig = all.not('.no-translation').eq(0).not(translation);
            }
            if (orig.length) {
              orig = orig.find('.l10n-string > span');
              translation.find('.l10n-string > span').each(function(i) {
                $(this).worddiff(orig.get(i), markup);
              });
            }
          }
        };

        translation.find('> .selector').click(function() {
          setStatus(translation, 'declined', false);
          // Mark this as the active translation, update others.
          setStatus(translation.siblings('.is-active:not(.new-translation)'), 'declined', true);
          setStatus(translation.siblings('.is-active'), 'active', false);
          translation.addClass('is-active');
        });

        // Update decline status based on checkbox values.
        translation.find('> .actions .declined :checkbox').change(function() {
          setStatus(translation, 'declined', this.checked);
        });

        translation.find('> .author span[title]').click(function() {
          var $this = $(this), html = $this.html();
          $this.html($this.attr('title')).attr('title', html);
        });

        if (isTranslation) {
          // Add doubleclick behavior to decline all other suggestions.
          translation.find('.l10n-string').dblclick(function() {
            translation.siblings().not('.new-translation').each(function () {
              setStatus($(this), 'declined', true);
            });
          });

          // Add hover behavior to update and remove diffs.
          translation
            .mouseenter(updateDiff)
            .mouseleave(removeDiff);
        }

        if (translation.is('.new-translation')) {
          translation.find('> .selector').click(function() {
            textareas.each(function() {
              var textarea = $(this);
              if (textarea.val() === '' || textarea.val() === textarea.attr('defaultValue')) {
                textarea.focus();
                // Stop checking the other ones.
                return false;
              }
            });
          });

          // Does any of the textareas have any content?
          var hasContent = function() {
            for (var i = 0; i < textareas.length; i++) {
              if (textareas[i].value && textareas[i].value !== textareas[i].defaultValue) {
                return true;
              }
            }
            return false;
          };

          var blurTimeout;
          textareas.each(function(n) {
            var wrapper = $(this);
            var textarea = $(this);
            var text = translation.find('.l10n-string > span').eq(n);

            textarea
              .focus(function() {
                translation.addClass('focussed');
                clearTimeout(blurTimeout);
                // Empty textarea when focused.
                if (textarea.val() === textarea.attr('defaultValue')) {
                  textarea.val('');
                }
              })
              .blur(function() {
                blurTimeout = setTimeout(function() {
                  translation.removeClass('focussed');
                  // Add back default value if user moved out and kept the original text.
                  if (textarea.val() === '') {
                    textarea.val(textarea.attr('defaultValue'));
                  }
                  translation[hasContent() ? 'addClass' : 'removeClass']('has-content');
                }, 1000);
              })
              .keyup(function() {
                // Encode and compute the diff for the text as text is typed.
                var val = encode(textarea.val());
                text
                  .data('worddiff:original', val)
                  .data('worddiff:markup', markup(val));
                var oldPos = textarea.offset().top;
                updateDiff();
                var diff = textarea.offset().top - oldPos;
                if (diff) {
                  window.scrollBy(0, diff);
                }
              });
          });
        }
      });
    });
  });
  
})(jQuery);
