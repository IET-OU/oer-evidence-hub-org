/*!
 Track events for JuxtaLearn quizzes and embeds.

 Google Analytics events: category, action, label, (value number)
*/

/*global jQuery:false, ga:false, ga_event:false */

jQuery(function ($) {

  'use strict';

  var $qView = $(".slickQuizWrapper .quizArea"),
    $score = $("#jlq-score"),
    $radar = $("svg [class *= radar-chart-serie]"),
    W = window,
    L = W.location.pathname,
    SE = W.simple_embed,
    JLQ = W.juxtalearn_quiz,
    quiz_label = JLQ && ('{ tt_id: ' + JLQ.tt_id + ', quiz_id: ' + JLQ.quiz_id + ' }'),
    embed_m = L.match(/(all-quiz-scores|quiz-score|quiz|map)/),
    embed_what = (embed_m && embed_m[1]) || 'other';

  log($score.data( 'quiz_name' ));

  if (!W.ga) {
    log("NOT defined: Google Analytics (ga)");
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


  $radar.on("mouseover", function() {
    //var tt = $("#jlq-score figcaption h2 a").text();
    var quiz_id = $score.data('quiz_id'),
      quiz_name = $score.data('quiz_name');

    ga_event('Score', 'Radar hover', 'Quiz: ' + quiz_name, quiz_id);
  })


  function ga_event(cat, act, label, val) {
    ga('send', 'event', cat, act, label, val);
    log(">>GA event", { cat: cat, act: act, label: label, val: val });
  }

  function log(s) {
    window.console && console.log(arguments);
  }

});
