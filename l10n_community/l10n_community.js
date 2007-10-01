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
  $('span.l10n-community-copy').append('<img src="' + imagePath + 'edit.png" class="action" alt="" />');
  $('span.l10n-community-copy').click(function() {
    var id = $(this).attr('id').replace('l10n-community-copy-', '');
    $('#l10n-community-translation-' + id.replace('-t', '')).val(Drupal.settings.l10n_strings[id]);
    $('#l10n-community-wrapper-' + parseInt(id.replace('-t', ''))).css('display', 'block');
  ;})

  $('#l10n-community-translate-form .toolbox').each(function(){
    
    // Add expand button to the toolbox.
    $(this).append($(document.createElement('IMG')).attr('src', imagePath + 'expand.png').attr('class', 'expand').attr('title', Drupal.settings.l10n_expand_help).click(function() {
      var id = $(this).parent().parent().attr('id').replace('l10n-community-editor-', '');
      // Reveal textareas where the translation is done (if those were hidden).
      $('#l10n-community-wrapper-' + id).css('display', 'block');
    ;}));
    
    // Add a lookup button to invoke server side callback.
    $(this).append($(document.createElement('IMG')).attr('src', imagePath + 'lookup.png').attr('class', 'lookup').attr('title', Drupal.settings.l10n_lookup_help).click(function() {
      var sid = $(this).parent().parent().attr('id').replace('l10n-community-editor-', '');
      // Ajax GET request to retrieve more details about this string.
      $.ajax({
        type: "GET",
        url: Drupal.settings.l10n_details_callback + sid,
        success: function (data) {
          // First empty, then load the data we get into the info pane.
          $('#l10n-community-editor-' + sid + ' .info-pane').css('display', 'block').empty().append(data);
        },
        error: function (xmlhttp) {
          // Being an internal/system error, this is not translatable.
          alert('An HTTP error '+ xmlhttp.status +' occured.\n'+ uri);
        }
      });
    ;}));
  });
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
        $('#l10n-community-editor-'+ sid +' .toolbox').append(Drupal.settings.l10n_approve_confirm);
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
 * Suggestion editing copy callback.
 */
l10nCommunity.copySuggestion = function(sid, translation) {
  if (translation.indexOf("\O")) {
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
