/* JuxtaLearn Quiz - scaffolding for the SlickQuiz editor.
*/

/*jslint indent: 2 */
/*global jQuery:false, window:false, log:false, console:false */

jQuery(function ($) {

  'use strict';

  var qEdit = $('.wp-admin .slickQuiz'),
    editAction = 'juxtalearn_quiz_edit',
    stumblesAction = 'juxtalearn_quiz_stumbling_blocks',
    problemsAction = 'juxtalearn_quiz_student_problems',
    tricky_topic_id,
    stumbling_blocks;

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
      url:  ajax_url(),
      data: {
        action: editAction,
        json: JSON.stringify(data)
      },
      dataType: 'text',
      async:   false, // for Safari
      success: function (data) {
        log(">> Ajax success! POST", editAction);
      }
    });

    log(">> Saving:", e.target.value);
  });

  $("select#jlq-trickytopic").on("change", function (e) {

    var tt_id = $("#jlq-trickytopic :selected").val();

    if (! tt_id) {
      log(">> No valid tricky topic selected.");
      return;
    }
    loading();

    tricky_topic_id = tt_id;

    $.getJSON(ajax_url(), { tricky_topic: tt_id, action: stumblesAction })
      .done(function (data, stat, jqXHR) {
        if ("success" === stat) {
          stumbling_blocks = data;
//TODO - react to "add question" click event.
          $(".JL-Quiz-Stumbles .jlq-stumbles-inner").html(data.html);
        }
        log(">> Get stumbling blocks, done. TT id:", tt_id, stat, data);
      })
      .always(function () {
        log(">> Get stumbling blocks, always. TT id:", tt_id);
        loading_end();
      });

  }).trigger("change");


  $(".jlq-stumbles-inner").on("click", "input", function (e) { //"click, change"?
    var $wrapper = $(this).closest(".jlq-stumbles-inner");
    var stumbles = $(":checked", $wrapper).val(); //$("input:checked", $wrapper).values();

    log(">> Stumbling blocks change:", stumbles, e);

    $.getJSON(ajax_url(), { stumbling_blocks: stumbles, action: problemsAction })
      .done(function (data, stat, jqXHR) {
        if ("success" === stat) {
          var $outer = $wrapper.closest(".JL-Quiz-Stumbles");
          var $scaffold = $(".jlq-scaffold-inner");

          $scaffold.html(data.html);
        }
        log(">> Get student problems, done:", stat, data);
      })
      .always(function () {
        loading_end();
      });
  });

  loading_end();


  function ajax_url() {
    return window.location.pathname
          .replace('admin.php', 'admin-ajax.php')
          .replace('slickquiz-preview', 'slickquiz-publish')
          + window.location.search
          + '&_JUXTALEARN_=1';
  }

  function loading() {
    $(".jlq-loading").show();
    $("body").addClass("jlq-body-loading");
    $("#jlq-tricktopic, .JL-Quiz-Stumbles input").prop("disabled", true);

    log(">> Loading start...");
  }
  function loading_end() {
    $(".jlq-loading").hide();
    $("body").removeClass("jlq-body-loading");
    $("#jlq-tricktopic, .JL-Quiz-Stumbles input").prop("disabled", false);

    log(">> Loading end.");
  }

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
