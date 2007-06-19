// $Id$
function l10n_community_init() {
  $('.form-item img').click(function() {
    var id = $(this).attr('id').replace('copy_button_', '');
    $('#new_suggestion' + Drupal.settings.translate_target[id-1]).val(Drupal.settings.translate[id-1]);
  ;})
}

$(function() {
  l10n_community_init();
});