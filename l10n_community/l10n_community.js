// $Id$

l10nCommunity = {};

/**
 * Initialize action images: toolboxes and string copy buttons.
 */
l10nCommunity.init = function() {
  // Hide textareas for strings where we already have translations.
  $('#l10n-community-translate-form .hidden').css('display', 'none'); 

  // Add information pane placeholder at the end.
  $('#l10n-community-translate-form fieldset').append('<div class="info-pane"></div>');

  var imagePath = Drupal.settings.l10n_image_path;
  
  // When the copy button is clicked, copy the original string value to the
  // translation field for the given strings. Relations are maintained with
  // the string ideitifiers.
  //$('span.l10n-community-copy').append('<img src="' + imagePath + 'edit.png" class="action" alt="" />');
  $('span.l10n-community-copy').click(function() {
    var id = $(this).attr('id').replace('l10n-community-copy-', '');
    $('#l10n-community-translation-' + id.replace('-t', '')).val(Drupal.settings.l10n_strings[id]);
    $('#l10n-community-wrapper-' + parseInt(id.replace('-t', ''))).css('display', 'block');
  ;})

  $('#l10n-community-translate-form .toolbox').each(function(){
    
    // Add a lookup button to invoke server side callback.
    $(this).append(l10nCommunity.formatButton('&#x2600;', Drupal.settings.l10n_lookup_help, 'lookup', function() {
      var sid = $(this).parent().parent().attr('id').replace('l10n-community-editor-', '');
      // Ajax GET request to retrieve more details about this string.
      $.ajax({
        type: "GET",
        url: Drupal.settings.l10n_details_callback + sid,
        success: function (data) {
          // First empty, then load the data we get into the info pane.
          $('#l10n-community-editor-' + sid + ' .info-pane').css('display', 'block').empty().append(data);
          $('#l10n-community-editor-' + sid + ' .lookup').addClass('disabled');
          // Hide the has-suggestion marker if we don't have suggestions anymore.
          // Could happen if we declined the last suggestion and reloading.
          $('#l10n-community-editor-' + sid + ' .l10n-no-suggestions').parent().parent().find('.l10n-has-suggestion').hide();
        },
        error: function (xmlhttp) {
          // Being an internal/system error, this is not translatable.
          alert('An HTTP error '+ xmlhttp.status +' occured.\n'+ uri);
        }
      });
    ;}));
    
    /*/ Add expand button to the toolbox.
    $(this).append(l10nCommunity.formatButton(Drupal.settings.l10n_expand_help, 'expand', function() {
      var id = $(this).parent().parent().attr('id').replace('l10n-community-editor-', '');
      // Reveal textareas where the translation is done (if those were hidden).
      $('#l10n-community-wrapper-' + id).css('display', 'block');
      $('#l10n-community-editor-' + id + ' .expand').addClass('disabled');
    }));*/
    
  });
}

/**
 * Add button formatted with the given data.
 */
l10nCommunity.formatButton = function(text, title, className, clickFunction) {
  return $(document.createElement('SPAN')).attr('class', className + ' l10n-button').attr('title', title).
         append($(document.createElement('B')).
         append($(document.createElement('B')).
         append($(document.createElement('B')).
         append(text)))).click(clickFunction);
}

/**
 * Suggestion approval callback.
 */
l10nCommunity.approveSuggestion = function(tid, sid) {
  // Invoke server side callback to save the approval.
  $.ajax({
    type: "GET",
    url: Drupal.settings.l10n_approve_callback + tid,
    success: function (data) {
      if (data == 'done') {
        // Hide and empty textarea(s), so it will not be used when saved.
        $('#l10n-community-wrapper-'+ sid).css('display', 'none');
        $('#l10n-community-translation-'+ sid).val('');
        // Hide info pane and inform user that the suggestion was saved.
        $('#l10n-community-editor-'+ sid +' .info-pane').css('display', 'none').empty();
        $('#l10n-community-editor-'+ sid +' .messagebox').append(Drupal.settings.l10n_approve_confirm);
      }
      else {
        alert(Drupal.settings.l10n_approve_error);
      };
    },
    error: function (xmlhttp) {
      // Being an internal/system error, this is not translatable.
      alert('An HTTP error '+ xmlhttp.status +' occured.\n'+ uri);
    }
  });
  // Return false for onclick handling.
  return false;
}

/**
 * Suggestion decline callback.
 */
l10nCommunity.declineSuggestion = function(tid, sid) {
  // Invoke server side callback to save the decline action.
  $.ajax({
    type: "GET",
    url: Drupal.settings.l10n_decline_callback + tid,
    success: function (data) {
      if (data == 'done') {
        // Reload info pane.
        $('#l10n-community-editor-'+ sid +' .lookup').click();
      }
      else {
        alert(Drupal.settings.l10n_decline_error);
      };
    },
    error: function (xmlhttp) {
      // Being an internal/system error, this is not translatable.
      alert('An HTTP error '+ xmlhttp.status +' occured.\n'+ uri);
    }
  });
  // Return false for onclick handling.
  return false;
}

/**
 * Suggestion editing copy callback.
 */
l10nCommunity.copySuggestion = function(sid, translation) {
  if (translation.indexOf("\O") > 0) {
    // If we have the null byte, suggestion has plurals, so we need to
    // copy over the distinct strings to the distinct textareas.
    var strings = translation.split("\O");
    for (string in strings) {
      $('#l10n-community-translation-'+ sid +'-'+ string).val(strings[string]);
    }
  }
  else {
    // Otherwise standard string.
    $('#l10n-community-translation-'+ sid).val(translation);
  }
  // Show the editing controls.
  $('#l10n-community-wrapper-'+ sid).css('display', 'block');
  return false;
}

// Global killswitch
if (Drupal.jsEnabled) {
  $(document).ready(l10nCommunity.init);
}
