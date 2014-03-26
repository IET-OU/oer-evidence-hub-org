/*
  JuxtaLearn - Facetious hack.
  Re-order the search form, promoting the "post-type" field.
*/

jQuery(function ($) {

  'use strict';

  var $form = $(".widget .facetious_form"),
    postType = ".facetious_post_type";

  $(postType, $form).addClass("original").hide();

  $(".facetious_search", $form).after("<p class='facetious_post_type hack'>");
  $(postType + ".hack").html($(postType + ".original").html());

  // Remove the duplicate field "name".
  $(postType + ".original").empty();

});
