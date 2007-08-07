// $Id$

function l10n_community_init() {
  // When the copy button is clicked, copy the original string value to the
  // translation field for the given strings. Relations are maintained with
  // the strings ideitifiers.
  $('img.l10n-community-copy').click(function() {
    var id = $(this).attr('id').replace('l10n-community-copy-', '');
    $('#l10n-commumnity-translation-' + id).val(Drupal.settings.l10n_strings[id]);
  ;})
  // Link-like behaviour with pointer mouse icon.
  $('.form-item img').mouseover(function() {
    $(this).css('cursor', 'pointer');
  ;})
}

$(function() {
  l10n_community_init();
});
