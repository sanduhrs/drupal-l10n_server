
(function ($) {
  encode = function (str) {
    str = String(str);
    var replace = { '&': '&amp;', '<': '&lt;', '>': '&gt;' };
    for (var character in replace) {
      var regex = new RegExp(character, 'g');
      str = str.replace(regex, replace[character]);
    }
    return str;
  };

  $('em.l10n-placeholder')
    .live('mouseover', function () {
      $(this).closest('tr').find('.l10n-placeholder:contains("' + $(this).text() + '")').addClass('highlight');
    })
    .live('mouseout', function () {
      $('.l10n-placeholder.highlight').removeClass('highlight');
    });

  $(function () {
    $('.l10n-more').click(function () {
      $(this)
        .addClass('loading')
        .parent().load(this.href);
      return false;
    });

    var markup = function (string) {
      // Highlight placeholders.
      string = string.replace(/([!@%]|<(ins|del)>[!@%]<\/(ins|del)>)(\w+|<(ins|del)>\w+<\/(ins|del)>)/g, '<em class="l10n-placeholder">$&</em>');

      // Wrap HTML tags in <code> tags.
      string = string.replace(/(&lt;.+?(&gt;|$))/g, function (str) {
        return '<code>' + str.replace(/<[^>]+>/g, '</code>$&<code>') + '</code>';
      });

      string = string.replace(/\\[^<]/g, '<span class="l10n-escape">$&</span>');
      string = string.replace(/\n/g, '<span class="l10n-nl"></span>$&');
      return string;
    };

    $('td.translation').parent().each(function () {
      var all = $('li.translation', this);
      var strings = all.find('.l10n-string > span');
      var source = $('td.source', this);

      source.find('.l10n-string span').each(function () {
        $(this).html(markup($(this).html()));
      });

      strings.each(function () {
        var orig = $(this).html(), markedUp = markup(orig);
        $(this)
          .html(markedUp)
          .data('worddiff:original', orig)
          .data('worddiff:markup', markedUp);
      });

      var setStatus = function (elem, status, value) {
        newValue = elem.find('.' + status + ' :checkbox').attr('checked', value).attr('checked');
        elem[(newValue === undefined ? value : newValue) ? 'addClass' : 'removeClass']('is-' + status);
      };

      var textareas = all.filter('.new-translation').find('textarea');

      $(this).find('ul.actions .edit').click(function () {
        var translation = $(this).closest('td.source, li.translation');
        var confirmed = undefined;
        textareas.each(function (i) {
          var textarea = $(this);
          var val = textarea.val();
          if (confirmed || val === textarea.attr('defaultValue') || !val || (confirmed === undefined && (confirmed = confirm("Do you want to overwrite the current suggestion?")))) {
            textarea.val(translation.find('.l10n-string > span:eq('+ i +')').text()).keyup();
            if (i == 0) {
              // Since we can't have multiple focuses, we jut focus the first textarea.
              textarea.focus();
            }
          }
        });
      });

      all.each(function () {
        var translation = $(this);
        var isTranslation = !translation.is('.no-translation');
        var siblings = all.not(this).not('.no-translation');

        var removeDiff = function () {
          strings.worddiffRevert();
        };

        var updateDiff = function () {
          removeDiff();
          if (isTranslation) {
            var orig = siblings.filter('.is-active');
            if (!orig.length)
              orig = siblings.filter('.default');
            if (!orig.length)
              orig = all.not('.no-translation').eq(0).not(translation);
            if (orig.length) {
              orig = orig.find('.l10n-string > span');
              translation.find('.l10n-string > span').each(function (i) {
                $(this).worddiff(orig.get(i), markup);
              });
            }
          }
        };

        translation.find('> .selector').click(function () {
          setStatus(translation, 'declined', false);
          // Mark this as the active translation.
          setStatus(translation.siblings('.is-active:not(.new-translation)'), 'declined', true);
          setStatus(translation.siblings('.is-active'), 'active', false);
          translation.addClass('is-active');
        });

        translation.find('> .actions .declined :checkbox').change(function () {
          setStatus(translation, 'declined', this.checked);
        });

        translation.find('> .actions .stable :checkbox').change(function () {
          setStatus(translation, 'stable', this.checked);
        });

        translation.find('> .author span[title]').click(function () {
          var $this = $(this), html = $this.html();
          $this.html($this.attr('title')).attr('title', html);
        });

        if (isTranslation) {
          translation.find('.l10n-string').dblclick(function () {
            translation.siblings().not('.new-translation').each(function () {
              setStatus($(this), 'declined', true);
            });
          });

          translation
            .mouseenter(updateDiff)
            .mouseleave(removeDiff);
        }

        if (translation.is('.new-translation')) {
          translation.find('> .selector').click(function () {
            textareas.each(function () {
              var textarea = $(this);
              if (textarea.val() === '' || textarea.val() === textarea.attr('defaultValue')) {
                textarea.focus();
                // Stop checking the other ones.
                return false;
              }
            });
          });

          var hasContent = function () {
            for (var i = 0; i < textareas.length; i++) {
              if (textareas[i].value && textareas[i].value !== textareas[i].defaultValue) {
                return true;
              }
            }
            return false;
          };

          var blurTimeout;
          textareas.each(function (n) {
            var wrapper = $(this);
            var textarea = $(this);
            var text = translation.find('.l10n-string > span').eq(n);

            textarea
              .focus(function () {
                translation.addClass('focussed');
                clearTimeout(blurTimeout);
                if (textarea.val() === textarea.attr('defaultValue')) {
                  textarea.val('');
                }
              })
              .blur(function () {
                blurTimeout = setTimeout(function () {
                  translation.removeClass('focussed');
                  if (textarea.val() === '') {
                    textarea.val(textarea.attr('defaultValue'));
                  }
                  translation[hasContent() ? 'addClass' : 'removeClass']('has-content');
                }, 1000);
              })
              .keyup(function () {
                var val = encode(textarea.val());
                text
                  .data('worddiff:original', val)
                  .data('worddiff:markup', markup(val));
                var oldPos = textarea.offset().top;
                updateDiff();
                var diff = textarea.offset().top - oldPos;
                if (diff)
                  window.scrollBy(0, diff);
              });
          });
        }
      });
    });
  });
})(jQuery);
