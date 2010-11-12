/* $Id$ */

(function($) {

  Drupal.behaviors.l10nPackagerExpand = function(context) {
    fixL10nPackagerTableClasses();
    $('tr.l10n-packager-summary a.expand:not(.l10n-packager-processed)', context).addClass('l10n-packager-processed').click(function() {
      var uri = $(this).parents('tr.l10n-packager-summary').attr('id').replace('l10n-packager-summary-', '');
      $(this).parents('tr.l10n-packager-summary').hide();
      $('tr.l10n-packager-detail-' + uri).fadeIn();
      fixL10nPackagerTableClasses();
      return false;
    });
  }
  
  function fixL10nPackagerTableClasses() {
    $('l10n-packager-download-dynamic tbody tr:visible:odd').addClass('odd').removeClass('even');
    $('l10n-packager-download-dynamic tbody tr:visible:even').addClass('even').removeClass('odd');
  }
 
})(jQuery);
