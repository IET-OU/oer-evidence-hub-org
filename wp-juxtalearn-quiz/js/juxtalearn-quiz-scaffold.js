/* JuxtaLearn Quiz scaffolding.
*/

jQuery(function ($) {
//$(function () {
//setTimeout(function () {

  var quiz = $('.slickQuiz'),
      action = 'juxtalearn_quiz_edit';

  var x = [ { a: 1 }, { a: "X" } ];

  console.log("Quiz scaffolding...", quiz, x);

/*
  var template_1 = $("#juxtalearn-quiz-template .jlq-1").parent().clone().html();

  console.log(template_1);
  console.log($(".wp-admin .slickQuiz .QuizTitle"));

  $(".wp-admin .slickQuiz .QuizTitle")
    .after($("#juxtalearn-quiz-template .jlq-1").parent().clone().html())
*/
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


  $('.addQuestion', quiz).on('click', function (e) {
    $('.questionSet', quiz).each(function (i, el) {
      if ($('.JL-Quiz-Stumbles', $(el)).length === 0) {
        $(el).after($(".jlq-template.jlq-t-s").html());
      }
    });
  });

  $('.publish', quiz).on('click', function (e) {
    e.preventDefault();

    var actionUrl = window.location.pathname
          .replace('admin.php', 'admin-ajax.php')
          .replace('slickquiz-preview', 'slickquiz-publish')
          + window.location.search
          + '&_JUXTALEARN_=1';

    var trickytopic = $('#jlq-trickytopic option:selected', quiz);
    var stumbles = [];
    $('.questionSet', quiz).each(function (i, el) {
      var q = $('[name = question]', $(el)).val();
      var s = $('.JL-Quiz-Stumbles input:checked', $(el)).values();
      //console.log(">> SBs", q, s);
      s = ["2", "3"];
      stumbles.push({ q: q, s: s });
    });

    var formJSON = JSON.stringify({
      trickytopic_id:   trickytopic.val(),
      trickytopic_name: trickytopic.text(),
      stumbling_blocks: stumbles
    });
    //console.log(">> Publish?", trickytopic.text(), stumbles);

    $.ajax({
      type: 'POST',
      url:  actionUrl,
      data: {
        action: action,
        json: formJSON
      },
      dataType:'text',
      async:   false, // for Safari
      success: function (data) {
        console.log(">> Success!");
      }
    });

    console.log(">> Publish");

  });

});
//}, 1000);
    