/* JuxtaLearn Quiz - scaffolding for the SlickQuiz editor.
*/

/*jslint indent:2, nomen:true, todo:true */
/*global jQuery:false, window:false, log:false, console:false, setTimeout:false, _t:false, loading:false */

jQuery(function ($) {

  'use strict';

  var qEdit = $('.wp-admin .slickQuiz'),
    editAction = 'juxtalearn_quiz_edit',
    stumblesAction = 'juxtalearn_quiz_stumbling_blocks',
    problemsAction = 'juxtalearn_quiz_student_problems',
    quiz_url = 'juxtalearn-quiz/%d/',
    scores_url = 'all-quiz-scores/%d/',
    tricky_topic_id,
    stumbling_blocks;

  log(">> JuxtaLearn Quiz scaffold.", qEdit);

  quiz_edit_default_texts();
  quiz_admin_table_links();

  // Quiz editor - insert scaffolding templates into page.
  $(".jlq-template").each(function (idx, el) {
    var selector = $(el).data("sel");
    $(selector).after($(el).html());
  });

  // Utility
  jQuery.fn.values = function () {
    var vals = [];
    $(this).each(function (i, el) {
      vals.push( $(el).val() );
    });
    return vals;
  }; /*jQuery.fn.values = function () {
    return $(this).map(function (i, el) {
      return $(el).val();
    });
  };*/


  // Quiz editor - insert from a template for each new question.
  $('a.addQuestion', qEdit).on('click', function (e) {
    setTimeout(function () {
      log('>> Add question');
      $('.questionSet', qEdit).each(function (i, el) {
        if ($('.JL-Quiz-Stumbles', $(el)).length === 0) {

          $('.actual', $(el)).after($(".jlq-template.jlq-t-s").html());

          var $stumbles = $(".jlq-stumbles-inner", $(el));
          if (stumbling_blocks) {
            $stumbles.html(stumbling_blocks.html);
          }
        }
      });
      loading_end();
    }, 400);
  });


  // Quiz editor - SAVE changes to scaffolding.
  $('button.publish, .draft, .preview', qEdit).on('click', function (e) {
    e.preventDefault();
    loading();

    var trickytopic = $('#jlq-trickytopic option:selected', qEdit),
        stumbles = [];

    $('.questionSet', qEdit).each(function (i, el) {
      var q = $('[name = question]', $(el)).val(),
        s = $('.JL-Quiz-Stumbles input:checked', $(el)).values();
      log(">> SBs", q, s);
      //s = ["2", "3"];
      stumbles.push({ q: q, s: s });
    });

    var data = {
      sub_action:       e.target.value || null, //"Publish", "Save Draft"
      trickytopic_id:   trickytopic.val(),
      trickytopic_name: trickytopic.text(),
      stumbling_blocks: stumbles
    };
    //log(">> Publish?", trickytopic.text(), stumbles);

    //TODO: new quiz race? Quiz ID!
    //setTimeout(function () {

    $.ajax({
      type: 'POST',
      url:  ajax_url(),
      data: {
        action: editAction,
        json: JSON.stringify(data)
      },
      dataType: 'text',
      async:   false // for Safari
    })
    .done(function (data) {
      log(">> Ajax success! POST", editAction);
    })
    .fail(function () {
      log(">> Save failed", editAction);
    })
    .always(loading_end);

    //}, 800);

    log(">> Saving:", e);
  });

  // Get stumbling block tags.
  $("select#jlq-trickytopic").on("change", function (e) {

    var tt_id = $("#jlq-trickytopic :selected").val();

    $(".jlq-scaffold-inner", qEdit).html(
      $(".jlq-template.jlq-t-dummy.scaffold").html());

    if (! tt_id) {
      log(">> No valid tricky topic selected.");
      $(".jlq-stumbles-inner", qEdit).html(
        $(".jlq-template.jlq-t-dummy.stumbles").html());

      return;
    }
    loading();

    tricky_topic_id = tt_id;

    $.getJSON(ajax_url(), { tricky_topic: tt_id, action: stumblesAction })
      .done(function (data, stat, jqXHR) {
        if ("success" === stat) {
          stumbling_blocks = data;
          $(".JL-Quiz-Stumbles .jlq-stumbles-inner").html(data.html);
        }
        log(">> Get stumbling blocks, done. TT id:", tt_id, stat, data);
      })
      .always(function () {
        log(">> Get stumbling blocks, always. TT id:", tt_id);
        loading_end();
      });

  }).trigger("change");


  // Get student problems - main scaffold (Delegated event).
  //$(".jlq-stumbles-inner").on("click", "input", ..)
  qEdit.on("click", ".jlq-stumbles-inner input", function (e) { //"click, change"?

    var $wrapper = $(this).closest(".jlq-stumbles-inner");
    var stumbles = $(":checked", $wrapper).values();

    log(">> Stumbling blocks change:", stumbles, e);

    if (!stumbles) { return; }

    loading();

    $.getJSON(ajax_url(), { stumbling_blocks: stumbles, action: problemsAction })
      .done(function (data, stat, jqXHR) {
        var $outer = $wrapper.closest(".JL-Quiz-Stumbles"),
          $scaffold = $(".jlq-scaffold-inner", $outer);

        if ("success" === stat && "ok" === data.stat) {

          // Temporary artificial delay.
          setTimeout(function () {
            $scaffold.html(data.html);
          }, 100);
        }
        log(">> Get student problems, done:", stat, data);
      })
      .always(function () {
        setTimeout(function () { loading_end(); }, 150);
      });
  });

  loading_end();


  /* ========= Utilities ========= */

  function ajax_url() {
    return window.location.pathname
          .replace('admin.php', 'admin-ajax.php')
          .replace('slickquiz-preview', 'slickquiz-publish')
          + window.location.search
          + '&_JUXTALEARN_=1';
  }

  // site_url(): Works in context of admin pages.
  function site_url(path) {
    return window.location.pathname.replace('wp-admin/admin.php', '') + path;
  }

  function loading() {
    $(".jlq-loading", qEdit).show();
    $("body").addClass("jlq-body-loading");
    $("#jlq-tricktopic, .JL-Quiz-Stumbles input").prop("disabled", true);
    $("[aria-live]", qEdit).attr("aria-busy", true); //".JL-Quiz-Stumbles"

    log(">> Loading start...");
  }
  function loading_end() {
    $(".jlq-loading", qEdit).hide();
    $("body").removeClass("jlq-body-loading");
    $("#jlq-tricktopic, .JL-Quiz-Stumbles input").prop("disabled", false);
    $("[aria-live]", qEdit).attr("aria-busy", false);

    log(">> Loading end.");
  }

  //Was: form_default_texts()
  function quiz_edit_default_texts() {
    var defaults = {
      MainCopy:   _t("Welcome! ..."),
      ResultCopy: _t("Well done! You've reached the end."),
      Level1small81100Bestsmall: _t("Prodigy"),
      Level2small6180small:     "Boffin",
      Level3small4160small:     "Mr/Mrs Average",
      Level4small2140small:     "Woops!",
      Level5small020Worstsmall: "Airhead",
      correct:   _t("Correct!"),
      incorrect: _t("Woops, that's wrong.")
    }, key, $inp;

    for (key in defaults) {
      $inp = $("[name =" + key + "]");
      if ("" === $inp.val()) {
        $inp.val(defaults[key]);
      }
    }

    //$("#toplevel_page_slickquiz [href...
    $("#adminmenu [href $= 'slickquiz']").attr("title", _t("SlickQuiz/ JuxtaLearn quizzes"));
    var $hd = $("h2", qEdit),
      ht = $hd.text();
    $hd.html(ht.replace("SlickQuiz", _t("SlickQuiz<i>/ JuxtaLearn</i>")));
  }

  //Was: add_admin_table_links()
  function quiz_admin_table_links() {
    var $tbl_name = $("table.quizzes td.table_name", qEdit);

    $tbl_name.each(function (j, el) {
      var text = $(el).text(),
        $row = $(el).closest("tr"),
        $scores = $row.children(".table_scores"),
        quiz_id = $row.children(".table_id").text(),
        qz_url = site_url(quiz_url).replace("%d", quiz_id),
        sc_url = site_url(scores_url).replace("%d", quiz_id);

      //if (!/^\d+/.test(quiz_id)) return;

      $(el).html('<a class=jlq-q href="' + qz_url + '">' + text + '</a>' +
          ' <a href="' + qz_url + '?embed=1" title="' + _t("Embed quiz: %s")
          .replace("%s", text) + '">' + _t("Embed") + '</a>');

      $scores.append(' <a class=jlq-v href="' + sc_url +
          '" title="' + _t("Visualize quiz scores") +
          '"><span>' + _t("Visualize") + '</span></a>');

      log(">> Quiz admin table:", text, qz_url);
    });
  }

  /* ========= I18n/ translation ========= */
  function _t(s) { return s; }

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
