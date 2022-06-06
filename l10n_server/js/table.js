(function ($, once) {
  Drupal.behaviors.l10nTable = {
    attach: function (context, settings) {
      once('l10n-table', 'html', context).forEach( function (element) {
        $('.region-content table tbody tr:visible:odd', context).addClass('odd');
        $('.region-content table tbody tr:visible:even', context).addClass('even');
      })
    }
  }
} (jQuery, once));
