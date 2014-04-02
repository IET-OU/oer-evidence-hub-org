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
    quiz_url = 'juxtalearn-quiz/%d/',
    scores_url = 'all-quiz-scores/%d/',
    tricky_topic_id,
    stumbling_blocks;

  log(">> JuxtaLearn Quiz scaffold.", qEdit);

  form_default_texts();
  add_admin_table_links();

  // Quiz editor - insert scaffolding templates into page.
  $(".jlq-template").each(function (idx, el) {
    var selector = $(el).data("sel");
    $(selector).after($(el).html());
  });

// BUG ?!
  jQuery.fn.values = function () {
    var vals = [];
    $(this).each(function (i, el) {
      vals.push( $(el).val() );
    });
    return vals;
  };


  // Quiz editor - insert from a template for each new question.
  $('.addQuestion', qEdit).on('click', function (e) {
    $('.questionSet', qEdit).each(function (i, el) {
      if ($('.JL-Quiz-Stumbles', $(el)).length === 0) {
        $(el).after($(".jlq-template.jlq-t-s").html());
      }
    });
  });

  // Quiz editor - SAVE changes to scaffolding.
  $('button.publish, .draft, .preview', qEdit).on('click', function (e) {
    e.preventDefault();

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

  // Get stumbling block tags.
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


  // Get student problems - main scaffold.
  $(".jlq-stumbles-inner").on("click", "input", function (e) { //"click, change"?
    loading();

    var $wrapper = $(this).closest(".jlq-stumbles-inner");
    var stumbles = $(":checked", $wrapper).val(); //$("input:checked", $wrapper).values();

    log(">> Stumbling blocks change:", stumbles, e);

    $.getJSON(ajax_url(), { stumbling_blocks: stumbles, action: problemsAction })
      .done(function (data, stat, jqXHR) {
        var $outer = $wrapper.closest(".JL-Quiz-Stumbles"),
          $scaffold = $(".jlq-scaffold-inner", $outer);

        if ("success" === stat && "ok" === data.stat) {

          // Temporary artificial delay.
          setTimeout(function () {
            $scaffold.html(data.html);
            if (data.activate_tax_tool) { activate_tax_tool(); }
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

  function form_default_texts() {
    var defaults = {
      MainCopy:   "Welcome! ...",
      ResultCopy: "Well done! You've reached the end.",
      Level1small81100Bestsmall:"Prodigy",
      Level2small6180small:     "Boffin",
      Level3small4160small:     "Mr/Mrs Average",
      Level4small2140small:     "Woops!",
      Level5small020Worstsmall: "Airhead",
      correct:   "Correct!",
      incorrect: "Woops, that's wrong."
    }, key, $inp;

    for (key in defaults) {
      $inp = $("[name =" + key + "]");
      if ("" === $inp.val()) {
        $inp.val(defaults[key]);
      }
    }
  }

  function add_admin_table_links() {
    var $tbl_name = $("td.table_name", qEdit);

    $tbl_name.each(function (j, el) {
      var text = $(el).text(),
        $row = $(el).closest("tr"),
        $scores = $row.children(".table_scores"),
        quiz_id = $row.children(".table_id").text(),
        qz_url = site_url(quiz_url).replace("%d", quiz_id),
        sc_url = site_url(scores_url).replace("%d", quiz_id);

      //if (!/^\d+/.test(quiz_id)) return;

      $(el).html('<a class=jlq-q href="' + qz_url + '">' + text + '</a>' +
          ' <a href="'+ qz_url +'?embed=1" title="Embed quiz: '+ text +'">Embed</a>');

      $scores.append(' <a class=jlq-v href="' + sc_url +
          '" title="Visualize scores"><span>Visualize</span></a>');

      log(">> Quiz admin table:", text, qz_url)
    });
  }

  function activate_tax_tool() {
    var $tabs = $("#juxtalearn_hub_tax_tabs", qEdit);

    if (!$tabs.tabs) {
      log("Error, jQuery UI tabs() not available");
    }
    log(">> Activate tax tool");

    // wp-juxtalearn-hub/js/admin.js#L46
    $tabs.tabs({
        activate: function (ev, ui) {
            $('.tax_term').hide();
            $('.tax_term').hide();
            $('div[class$="desc"]').show();
        }
    }).css("min-height", "150px");
	$tabs.find('label').hover(function () {
		$('.tax_term').hide();
		$(this).css('text-decoration', 'underline');
		var labelName = $(this).attr('for');
		$('.tax_term.'+labelName).fadeIn(500);
	}, function () {
		$('.tax_term').hide();
		$('div[class$="desc"]').fadeIn(500);
		$(this).css('text-decoration');
	});
	$("input[type=checkbox]", $tabs).prop("disabled", "disabled");
	$tabs.show();
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
