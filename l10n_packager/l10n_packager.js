/* $Id$ */

(function($) {

  $('tr.l10n-packager-summary a.expand').click(function() {
    var uri = $(this).parents('tr.l10n-packager-summary').attr('id').replace('l10n-packager-summary-', '');
    $(this).parents('tr.l10n-packager-summary').fadeOut();
    $('tr.l10n-packager-detail-' + uri).fadeIn();
    return false;
  });

})(jQuery);
