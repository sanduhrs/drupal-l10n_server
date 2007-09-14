// $Id$

l10nCommunity = {};

/**
 * Initialize action images: toolboxes and string copy buttons.
 */
l10nCommunity.init = function() {
  // Hide textareas for strings where we already have translations.
  $('#l10n-community-translate-form .hidden').css('display', 'none'); 

  // Add information pane placeholder.
  $('#l10n-community-translate-form fieldset').append('<div class="info-pane"></div>');

  var imagePath = Drupal.settings.l10n_image_path;
  
  // When the copy button is clicked, copy the original string value to the
  // translation field for the given strings. Relations are maintained with
  // the strings ideitifiers.
  $('span.l10n-community-copy').append('<img src="' + imagePath + 'edit.png" class="copy" alt="" />');
  $('span.l10n-community-copy').click(function() {
    var id = $(this).attr('id').replace('l10n-community-copy-', '');
    $('#l10n-community-new-translation-' + id.replace('-t', '')).val(Drupal.settings.l10n_strings[id]);
    $('.l10n-community-wrapper-' + id.replace('-t', '')).css('display', 'block');
  ;})

  $('#l10n-community-translate-form .toolbox').each(function(){
    // Add expand button to the toolbox.
    $(this).append($(document.createElement('IMG')).attr('src', imagePath + 'expand.png').attr('class', 'expand').attr('title', Drupal.settings.l10n_expand_help).click(function() {
      var id = $(this).parent().attr('id').replace('l10n-community-toolbox-', '');
      // Reveal textareas where the translation is done (if those were hidden).
      $('.l10n-community-wrapper-' + id).css('display', 'block');
    ;}));
    // Add a lookup button to invoke server side callback.
    $(this).append($(document.createElement('IMG')).attr('src', imagePath + 'lookup.png').attr('class', 'lookup').attr('title', Drupal.settings.l10n_lookup_help).click(function() {
      var uri = $(this).parent().parent().children('.l10n-community-sid-callback').attr('value');
      // Ajax GET request to retrieve more details about this string.
      $.ajax({
        type: "GET",
        url: uri,
        success: function (data) {
          var parts = data.split("\n\n");
          $('#l10n-community-fields-' + parts[0] + ' .info-pane').css('display', 'block').empty().append(parts[1]);
        },
        error: function (xmlhttp) {
          alert('An HTTP error '+ xmlhttp.status +' occured.\n'+ uri);
        }
      });
    ;}));
  });
}

l10nCommunity.approveSuggestion = function(uri, textareaId, sid) {
  // Invoke server side callback to save the approval.
  $.ajax({
    type: "GET",
    url: uri,
    success: function (data) {
      if (data == 'done') {
        // Hide and empty textarea, so it will not be used when saved.
        $('.l10n-community-wrapper-' + textareaId).css('display', 'none');
        $('#l10n-community-new-translation-' + textareaId).val('');
        $('#l10n-community-fields-' + sid + ' .info-pane').css('display', 'none').empty();
        $('#l10n-community-toolbox-' + textareaId).append('Suggestion approved.');
      }
      else {
        alert('There was an error approving this suggestion. You might not have permission or the suggestion id was invalid.');
      };
    },
    error: function (xmlhttp) {
      alert('An HTTP error '+ xmlhttp.status +' occured.\n'+ uri);
    }
  });
}

// Global killswitch
if (Drupal.jsEnabled) {
  $(document).ready(l10nCommunity.init);
}
