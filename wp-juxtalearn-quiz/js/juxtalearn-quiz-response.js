/*
JuxtaLearn Quiz - submit scores for individual questions.

https://github.com/wp-plugins/slickquiz/blob/master/php/slickquiz-front.php#L152
https://github.com/jewlofthelotus/SlickQuiz
*/

jQuery(function ($) {

  'use strict';

  var qView = $(".entry-content .slickQuizWrapper"),
    action = 'juxtalearn_quiz_scores';
    //JQ = window.juxtalearn_quiz || {};

  log("Quiz response: ", qView);

  $(".button.startQuiz", qView).on("click", function () {
    alert("Start click");
  });


//BUG ?!
  $(".button.checkAnswer").last().on("click", function (e) {

    alert("Check click");
    log("Check click: ", qView);
    
    var the_qz = $(this).closest(".slickQuizWrapper"); //$(e.target)..

    setTimeout(function () {
      var quiz_id = the_qz.attr("id").replace("slickQuiz"),
          quiz_name = $(".quizName", the_qz).text(),
          responses = [];

      $("#slickQuiz" + quiz_id +" .question").each(function (q_num, el) {
        responses.push({
          is_correct: $(el).hasClass("correctResponse"),
          q_text: $("h3", $(el)).text(),
          q_num : q_num
        });
      });

      var data = {
        user_id:   '?',  //Or, user name for not logged in users
        quiz_id:   quiz_id,
        quiz_name: quiz_name,
        responses: responses
      };

      alert("Responses: " + quiz_name);
      log(">> Responses: ", data, the_q);

      /*$.ajax({
        type: "POST",
        url: <?php esc_url( wp_nonce( site_url("wp-admin/admin-ajax.php"), ..
        data: { action: action, json: JSON.stringify(data) },
        success: function () { log("Ajax success"); }
      });*/

    }, 2000);

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

