/*
JuxtaLearn Quiz - submit scores for individual questions.

https://github.com/wp-plugins/slickquiz/blob/master/php/slickquiz-front.php#L152
https://github.com/jewlofthelotus/SlickQuiz
*/

jQuery(function ($) {

  'use strict';

  var $qView = $(".entry-content .slickQuizWrapper"),
    action = 'juxtalearn_quiz_scores',
    JQ = window.juxtalearn_quiz || {};

  log("Quiz response: ", $qView);

  $(".X--button.startQuiz", $qView).on("click", function () {
    alert("Start click");
  });

  $qView.on("click", ".button.checkAnswer:last", function (e) {

    //alert("Check click");
    log("Check-answer click: ", $qView);

    var $the_qz = $(this).closest(".slickQuizWrapper"); //$(e.target)..

    setTimeout(function () {
      var quiz_id = $the_qz.attr("id").replace("slickQuiz", ""),
          quiz_name = $(".quizName", $the_qz).text(),
          responses = [];

      $("#slickQuiz" + quiz_id +" .question").each(function (q_num, el) {
        responses.push({
          is_correct: $(el).hasClass("correctResponse"),
          q_text: $("h3", $(el)).text(),
          q_num : q_num
        });
      });

      var data = {
        user_id:   '? todo ?',  //Or, user name for not logged in users
        quiz_id:   quiz_id,
        quiz_name: quiz_name,
        responses: responses
      };

      log(">> Quiz responses: ", data, $the_qz);

      $.post(ajax_url(), { action: action, json: JSON.stringify(data) })
        .done(function (data, stat, jqXHR) {
          log(">> Scores submitted, done:", stat);
        });

    }, 2200);

  });

  function ajax_url() {
    return JQ.ajaxurl + '&_JUXTALEARN_=1';
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

