/*!
 Track events for JuxtaLearn quizzes and embeds.

 Google Analytics events: category, action, label, (value number)
*/

/*global jQuery:false, ga:false, ga_event:false */

jQuery(function ($) {

  'use strict';

  var $qView = $(".slickQuizWrapper .quizArea"),
    W = window,
    L = W.location.pathname,
    SE = W.simple_embed,
    JLQ = W.juxtalearn_quiz,
    quiz_label = JLQ && ('{ tt_id: ' + JLQ.tt_id + ', quiz_id: ' + JLQ.quiz_id + ' }'),
    embed_what = L.match(/(all-quiz-scores|quiz-score|quiz|map)/)[1] || 'other';

  if (!ga) {
    return;
  }

  if (SE) {
    ga_event('Embed', embed_what, SE.host_url, JLQ && JLQ.quiz_id);
  }


  $qView.on("click", ".button.startQuiz", function () {
    ga_event('Quiz', 'Start', quiz_label, JLQ.quiz_id);
  });

  $qView.on("click", ".button.checkAnswer", function () {
    ga_event('Quiz', 'Check answer', quiz_label, JLQ.quiz_id);
  });

  $qView.on("click", ".button.checkAnswer:last", function () {
    ga_event('Quiz', 'End', quiz_label, JLQ.quiz_id);
  });


  function ga_event(cat, act, label, val) {
    ga('send', 'event', cat, act, label, val);
    log(">>GA event", { cat: cat, act: act, label: label, val: val });
  }

  function log(s) {
    window.console && console.log(arguments);
  }

});
