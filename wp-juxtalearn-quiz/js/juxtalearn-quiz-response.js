/*
JuxtaLearn Quiz - submit scores for individual questions.

https://github.com/wp-plugins/slickquiz/blob/master/php/slickquiz-front.php#L152
https://github.com/jewlofthelotus/SlickQuiz
*/

/*jslint indent: 2 */
/*global jQuery:false, window:false, setTimeout:false, console:false, ajax_url:false, log:false */

jQuery(function ($) {

  'use strict';

  var $qView = $(".entry-content .slickQuizWrapper"),
    action = 'juxtalearn_quiz_scores',
    time_start,
    JQ = window.juxtalearn_quiz || {};

  log(">> JuxtaLearn Quiz response.", $qView);

  $qView.on("click", ".button.startQuiz", function () {
    time_start = new Date();
    log("Start click:", time_start);
  });

//Bug ?!
  $qView.on("click", ".button.checkAnswer:last", function () {
    var time_end = new Date(),
      $the_qz = $(this).closest(".slickQuizWrapper"); //$(e.target)..

    log("Check-answer click: ", $qView);

    setTimeout(function () {
      var quiz_id = $the_qz.attr("id").replace("slickQuiz", ""),
        quiz_name = $(".quizName", $the_qz).text(),
        user_name = $(".nameLabel input", $the_qz).val(),
        user_email = $(".emailLabel input", $the_qz).val(),
        data = {},
        responses = [];

      $(".question", $the_qz).each(function (q_num, el) {
        responses.push({
          is_correct: $(el).hasClass("correctResponse"),
          q_text: $("h3", $(el)).text(),
          q_num : q_num
        });
      });

      data = {
        user_name:  user_name,  //Or, user name for not logged in users
        user_email: user_email,
        quiz_id:    quiz_id,
        quiz_name:  quiz_name,
        time_start: time_start.toUTCString(), //toISOString() - IE > 8.
        time_end:   time_end.toUTCString(),
        time_diff_ms: time_end - time_start,
        responses:  responses
      };

      log(">> Quiz responses: ", data, $the_qz);

      $.post(ajax_url(), { action: action, json: JSON.stringify(data) })
        .done(function (data, stat, jqXHR) {
          log(">> Scores submitted, done:", stat);
        });

    }, 2500); // SlickQuiz: 2000.

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
    console.log(Array.prototype.slice.call(arguments));
  }
};

