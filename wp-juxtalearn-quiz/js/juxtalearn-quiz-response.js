/*
JuxtaLearn Quiz - submit scores for individual questions.

https://github.com/wp-plugins/slickquiz/blob/master/php/slickquiz-front.php#L152
https://github.com/jewlofthelotus/SlickQuiz
*/

jQuery(function ($) {

  'use strict';

  var $qView = $(".entry-content .slickQuizWrapper .quizArea"),
    action = 'juxtalearn_quiz_scores',
    time_click = 2000 + 1000, //SlickQuiz: 2000ms.
    time_start,
    JLQ = window.juxtalearn_quiz || {};

  if (!JLQ.tt_id) {
    log(">> Warning: just a plain SlickQuiz? No linked Tricky Topic.");
    return;
  }
  log(">> JuxtaLearn Quiz response.", $qView);

  $qView.on("click", ".button.startQuiz", function () {
    time_start = new Date();
    log("Start click:", time_start);
  });

//Bug ?!
  $qView.on("click", ".button.checkAnswer:last", function () {
    var time_end = new Date(),
      $post = $(this).closest("article"),
      $the_qz = $(this).closest(".slickQuizWrapper"); //$(e.target)..

    log("Check-answer click: ", $qView);

    setTimeout(function () {
      var quiz_id = $the_qz.attr("id").replace("slickQuiz", ""),
        quiz_name = $(".quizName", $the_qz).text(),
        post_id = $post.attr("id").replace("post-", ""),
        user_name = $(".nameLabel input", $the_qz).val() || null,
        user_email = $(".emailLabel input", $the_qz).val() || null,
        data = {},
        responses = [];

      $(".question", $the_qz).each(function (q_num, el) {
        responses.push({
          is_correct: $(el).hasClass("correctResponse"),
          q_text: $("h3", $(el)).text(),
          q_num : q_num  //TODO: can we do better? A proper question ID.
        });
      });

      data = {
        user_name:  user_name,  //Or, user name for not logged in users
        user_email: user_email, //|| JLQ.user_email
        quiz_id:    quiz_id,
        quiz_name:  quiz_name,
        tt_id:      JLQ.tt_id,
        post_id:    post_id,
        time_start: time_start.toUTCString(), //toISOString() - IE > 8.
        time_end:   time_end.toUTCString(),
        time_diff_ms: time_end - time_start,
        responses:  responses
      };

      log(">> Quiz responses: ", data, $the_qz);

      $.post(ajax_url(), { action: action, json: JSON.stringify(data) })
        .done(function (data, stat, jqXHR) {
          log(">> Scores submitted, OK:", data.url, jqXHR);
          $the_qz.append(data.html);
        })
        .fail(function (jqXHR, stat) {
          //var msg = jqXHR.responseJSON.msg;
          log(">> Fail:", stat, jqXHR);
        });

    }, time_click);

  });

  function ajax_url() {
    return JLQ.ajaxurl + '&_JUXTALEARN_=1';
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

