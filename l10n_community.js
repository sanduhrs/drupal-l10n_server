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

  // When the copy button is clicked, copy the original string value to the
  // translation field for the given strings. Relations are maintained with
  // the strings ideitifiers.
  $('span.l10n-community-copy').append('<img src="/l10n_server/sites/all/modules/l10n_server/images/edit.png" class="copy" alt="" />');
  $('span.l10n-community-copy').click(function() {
    var id = $(this).attr('id').replace('l10n-community-copy-', '');
    $('#l10n-commumnity-new-translation-' + id.replace('-t', '')).val(Drupal.settings.l10n_strings[id]);
  ;})

  $('#l10n-community-translate-form .toolbox').each(function(){
    // Add expand button to the toolbox.
    $(this).append($(document.createElement('IMG')).attr('src', '/l10n_server/sites/all/modules/l10n_server/images/expand.png').attr('class', 'expand').click(function() {
      var id = $(this).parent().attr('id').replace('l10n-community-toolbox-', '');
      // Reveal textareas where the translation is done (if those were hidden).
      $('.l10n-commumnity-wrapper-' + id).css('display', 'block');
    ;}));
    // Add a lookup button to invoke server side callback.
    $(this).append($(document.createElement('IMG')).attr('src', '/l10n_server/sites/all/modules/l10n_server/images/lookup.png').attr('class', 'lookup').click(function() {
      var uri = $(this).parent().parent().children('.l10n-community-sid-callback').attr('value');
      // Ajax GET request to retrieve more details about this string.
      $.ajax({
        type: "GET",
        url: uri,
        success: function (data) {
          var parts = data.split("\n\n");
          $('#l10n-community-fields-' + parts[0] + ' .info-pane').empty().append(parts[1]);
        },
        error: function (xmlhttp) {
          alert('An HTTP error '+ xmlhttp.status +' occured.\n'+ uri);
        }
      });
    ;}));
  });
}

// Global killswitch
if (Drupal.jsEnabled) {
  $(document).ready(l10nCommunity.init);
}
