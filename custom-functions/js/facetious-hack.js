/*!
  JuxtaLearn - Facetious hack(s).
  Re-order the search form, promoting the "post-type" and subject fields.
*/

jQuery(function ($) {

  'use strict';

  var $form = $(".widget .facetious_form"),
    subject = ".facetious_juxtalearn_hub_subject",
    postType = ".facetious_post_type",
    hack = ".hack";

  $(postType, $form).addClass("original").hide();
  $(subject, $form).addClass("original").hide();

  $(".facetious_search", $form).after("<p class='facetious_post_type hack'>");
  $(postType + hack).html($(postType + ".original").html());

  // Put 'subject' after post-type?
  $(postType + hack, $form).after("<p class='" + subject + " hack'>");
  $(subject + hack).html($(subject + ".original").html());

  // Remove the duplicate field "name".
  $(postType + ".original").empty();
  $(subject + ".original").empty();

});
