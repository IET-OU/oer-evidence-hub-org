/* JuxtaLearn Quiz - scaffolding for the SlickQuiz editor.
*/

/*jslint indent: 2 */
/*global jQuery:false, window:false, log:false, console:false */

jQuery(function ($) {

  'use strict';

  var qEdit = $('.wp-admin .slickQuiz'),
    editAction = 'juxtalearn_quiz_edit',
    sbAction = 'juxtalearn_stumbling_blocks';

  log(">> Quiz scaffolding...", qEdit);

  // Quiz editor - insert scaffolding templates into page.
  $(".jlq-template").each(function (idx, el) {
    var selector = $(el).data("sel");
    $(selector).after($(el).html());
  });

// BUG ?!
  jQuery.fn.values = function () {
    return $(this).map(function (i, el) {
      return $(el).val();
    });
  };


  // Quiz editor - insert from a template for each new question.
  $('.addQuestion', qEdit).on('click', function (e) {
    $('.questionSet', qEdit).each(function (i, el) {
      if ($('.JL-Quiz-Stumbles', $(el)).length === 0) {
        $(el).after($(".jlq-template.jlq-t-s").html());
      }
    });
  });

  // Quiz editor - submit changes to scaffolding.
  $('button.publish, .draft, .preview', qEdit).on('click', function (e) {
    e.preventDefault();

    var actionUrl = window.location.pathname
          .replace('admin.php', 'admin-ajax.php')
          .replace('slickquiz-preview', 'slickquiz-publish')
          + window.location.search
          + '&_JUXTALEARN_=1';

    var trickytopic = $('#jlq-trickytopic option:selected', qEdit),
        stumbles = [];
    $('.questionSet', qEdit).each(function (i, el) {
      var q = $('[name = question]', $(el)).val(),
        s = $('.JL-Quiz-Stumbles input:checked', $(el)).values();
      //log(">> SBs", q, s);
      s = ["2", "3"];
      stumbles.push({ q: q, s: s });
    });

    var data = {
      sub_action:       e.target.value,
      trickytopic_id:   trickytopic.val(),
      trickytopic_name: trickytopic.text(),
      stumbling_blocks: stumbles
    };
    //log(">> Publish?", trickytopic.text(), stumbles);

    $.ajax({
      type: 'POST',
      url:  actionUrl,
      data: {
        action: editAction,
        json: JSON.stringify(data)
      },
      dataType: 'text',
      async:   false, // for Safari
      success: function (data) {
        log(">> Ajax success!");
      }
    });

    log(">> Saving:", e.target.value);
  });

});


// usage: log('inside coolFunc',this,arguments);
// http://paulirish.com/2009/log-a-lightweight-wrapper-for-consolelog/
window.log = function () {
  log.history = log.history || [];   // store logs to an array for reference
  log.history.push(arguments);
  if (this.console) {
    console.log( Array.prototype.slice.call(arguments) );
  }
};
