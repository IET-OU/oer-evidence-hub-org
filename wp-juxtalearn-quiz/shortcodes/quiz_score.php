<?php
/**
 * Wordpress shortcode to visualize a JuxtaLearn quiz score for a single attempt.
 *
 * Usage:
 *   [quiz_score] - With `my-page/{SQ SCORE ID}/`
 *   [quiz_score id={SQ SCORE ID}]
 *
 * @copyright RadarChart.js - Copyright 2013 Nadieh Bremer (@nbremer).
 * @link http://bl.ocks.org/nbremer/6506614#RadarChart.js
 *
 * @copyright 2014 The Open University (IET).
 * @author Nick Freear.
 * @package JuxtaLearn_Quiz
 */

class JuxtaLearn_Quiz_Shortcode_Score extends JuxtaLearn_Quiz_Shortcode {

  const SHORTCODE = 'quiz_score';

  protected $offset;
  protected $divisor;
  protected $chart_size; //pixels

  public function __construct() {
    add_shortcode(self::SHORTCODE, array(&$this, 'quiz_score_shortcode'));
  }

  protected function set_score_options() {
    $this->offset = intval($this->_get('offset', 1));
    $this->divisor = $this->_get('divisor', 'max_score');
    $this->chart_size = intval($this->_get('size', 500));
  }

  public function quiz_score_shortcode($attrs, $content = '', $name) {
    //Was: $jlq_score_id
    $sq_score_id = $this->url_parse_id($attrs);
    $this->set_score_options();

    $score = $this->model_get_score($sq_score_id, $this->offset);
    $permission = isset($score->permission) ? $score->permission : NULL;

    $b_continue = $this->auth_permitted($score->createdBy, $permission, $auth_reason);
    if (!$b_continue) {
      return;
    }
    ?>

    <!--JLQ AUTH: <?php echo $auth_reason ?> -->
    <?php if (!$score->tricky_topic_id): ?>
      <p class="jl-error-msg no-tt"><?php echo sprintf(
        __('Warning: %s', self::LOC_DOMAIN), $score->warning) ?>
        <?php echo sprintf(__('Quiz ID: %d', self::LOC_DOMAIN), $score->quiz_id) ?></p>
      <?php return; ?>
    <?php endif;

    $this->print_score_markup($score);
    ?>

    <script src=
    "<?php echo plugins_url('js/radar-charts-d3.js', JUXTALEARN_QUIZ_REGISTER_FILE) ?>"
    ></script>
    <script>
    <?php $this->print_spider_javascript(array($score)) ?>
    </script>

<?php
    $this->print_utility_scripts($score);
  }

  protected function print_utility_scripts($score) {
    ?>
    <script>
    var JLQ_score_data = <?php echo json_encode($score) ?>;
    window.console && console.log(">> Score data:", JLQ_score_data);
    </script>

    <script>
    jQuery(function ($) {
      var $meta = $(".simple-embed #jlq-score-meta");
      $("#jlq-score .jlq-score-bn").click(function () {
        $meta.toggle();
      });
      $meta.hide();
    });
    </script>

    <script>
    document.documentElement.className += " shortcode-<?php echo self::SHORTCODE ?>";
    </script>
<?php
  }

  protected function print_score_markup($score, $notes = NULL) {

    $offset = $score->offset;
  ?>
    <div id=jlq-score >
    <style> .jlq-score-bn { display: none; } </style>

    <figure id=jlq-score-chart aria-labelledby="jlq-score-caption" role="img">
    <figcaption>
    <h2 id="jlq-score-caption"><?php echo sprintf( __(
'Spider or radar chart of cumulative quiz scores versus stumbling blocks, for the <a %s>%s tricky topic</a>.',
        self::LOC_DOMAIN), "href='$score->tricky_topic_url'", $score->tricky_topic_title) ?>
    <small>(Offset: <?php echo $offset ?>)</small></h2>

    <?php if ($notes): ?>
      <p class=notes ><?php echo $notes ?></p>
    <?php endif; ?>

    <?php if (0 == count($score->stumbling_blocks)): ?>
    <p class="jl-error-msg no-sbs">ERROR. Sorry! I couldn't get any stumbling blocks. A bug maybe? :(
      <?php if (isset($score->warning)):?><small><?php echo sprintf(
        __('Warning: %s', self::LOC_DOMAIN), $score->warning) ?></small><?php endif;?>
    </p>
    <?php elseif (count($score->stumbling_blocks) < 3): ?>
    <small class="jl-warn-msg low-sbs"><?php echo
    __('(Note: we have less than 3 stumbling blocks, so the chart won\'t look great!)',
        self::LOC_DOMAIN) ?></small>
  <?php endif; ?>

    </figcaption>
    <div id=jlq-score-body >
        <div id=jlq-score-chart >
<!--[if lte IE 8]>
            <div class="jl-chart-no-js">
            <p>Unfortunately, the chart doesn't work in older browsers. Please <a
            href="http://whatbrowser.org/">try a different browser</a>.
            </div>
<![endif]-->
            <div id="loading" class="jl-chart-loading"
              ><?php echo __('Loading chart...', self::LOC_DOMAIN) ?></div>
        </div>
    </div>
    </figure>

    <button class=jlq-score-bn title="Show quiz data">Show</button>
    <div id=jlq-score-meta >
    <button class=jlq-score-bn title="Hide quiz data">Hide</button>
    <ul>
    <li> Quiz title:   <?php echo $score->quiz_name ?>
    <li> Quiz completed: <?php echo $score->endDate ?>
    <li> Tricky Topic: <a href="<?php echo $score->tricky_topic_url ?>"><?php
         echo $score->tricky_topic_title ?></a>
    <li> User name:    <?php echo $score->user_name ?>
    </ul>

    <table id=jlq-score-table >
      <tr><th>Stumbling block</th> <th>Questions</th> <th>Scores</th></tr>

<?php foreach ($score->stumbling_blocks as $sb_id => $sb): ?>
      <tr><td>SB <?php echo $sb_id .': '. $sb['sb'] ?></td> <td><?php echo $sb['qs'] ?></td> <td><?php echo $sb['score'] - $offset ?></td></tr>
<?php endforeach; ?>
    </table>
    </div>

    </div>
    <?php
  }


  protected function model_get_score($score_id, $offset) {
    $model = new JuxtaLearn_Quiz_Model();
    $score = $model->get_score($score_id, $offset);
    if (!$score) {
      $this->error_404(__('Invalid score ID: ', self::LOC_DOMAIN) . $score_id);
    }
    return $score;
  }

  protected function print_spider_javascript($the_scores) {
    # http://bl.ocks.org/nbremer/6506614#RadarChart.js

    $num_scores = count($the_scores);
    $max_score = (float) $the_scores[0]->maximum_score; #?

    if ('max_score' == $this->divisor) {
      $divisor = $max_score;
      $format = '%';
    } else {
      $divisor = floatval($this->divisor);
      $format = '01.1f';
    }
    if ($divisor <= 0) {
      $divisor = 1;
    }
    $meta = json_encode(array(
      'divisor' => $divisor,
      'max_score' => $max_score,
      'offset' => $this->offset,
      'format' => $format,
    ));

    ?>
/*jslint devel: true, vars: true, white: true, indent: 2 */
/*global jQuery:false, window:false, d3:false, RadarChart:false */

jQuery(function ($) {

  'use strict';

  if (!window.d3) {
    return;
  }

  console.log(">> Chart start - d3 exists. Meta-data:", <?php echo $meta ?> );

  $(".jl-chart-loading").hide();

  var w = <?php echo $this->chart_size ?>,
	h = <?php echo $this->chart_size ?>;

var colorscale = d3.scale.category10();

//Legend titles
var LegendOptions = [
<?php foreach ($the_scores as $j => $score): ?>
<?php
    $max_score = $score->maximum_score > $max_score ? $score->maximum_score : $max_score;
?>
    '<?php echo $score->user_name .' / '. $score->endDate
            ?>'<?php echo $j < ($num_scores - 1) ? ',':'' ?>

<?php endforeach; ?>
];

//Data
var d = [
<?php foreach ($the_scores as $j => $score): ?>
<?php
    $sb_limit = count($score->stumbling_blocks);
    $sb_count = 0;
?>
		[
    <?php foreach ($score->stumbling_blocks as $sb_id => $sb): ?>
		{axis: "<?php echo $sb['sb'] .' (SB:'. $sb_id .')' ?>", value: <?php
		    echo $sb['score'] / $divisor ?> }<?php $sb_count++; echo $sb_count < $sb_limit ? ',':''; ?>

    <?php endforeach; ?>
		]<?php echo $j < ($num_scores - 1) ? ',':'' ?>

<?php endforeach; ?>
];

//Options for the Radar chart, other than default
var mycfg = {
  w: w,
  h: h,
  maxValue: <?php echo $max_score //0.6 ?>,
  format: '<?php echo $format ?>',
  levels: 6,
  ExtraWidthX: 300
};

//Call function to draw the Radar chart
//Will expect that data is in %'s
RadarChart.draw("#jlq-score-chart", d, mycfg);

////////////////////////////////////////////
/////////// Initiate legend ////////////////
////////////////////////////////////////////

var svg = d3.select('#jlq-score-body')
	.selectAll('svg')
	.append('svg')
	.attr("width", w+300)
	.attr("height", h)

//Create the title for the legend
var text = svg.append("text")
	.attr("class", "title")
	.attr('transform', 'translate(90,0)')
	.attr("x", w - 70)
	.attr("y", 10)
	.attr("font-size", "12px")
	.attr("fill", "#404040")
	.text("<?php echo __('Students who completed the quiz', self::LOC_DOMAIN) ?>");

//Initiate Legend
var legend = svg.append("g")
	.attr("class", "legend")
	.attr("height", 100)
	.attr("width", 200)
	.attr('transform', 'translate(90,20)')
	;
	//Create colour squares
	legend.selectAll('rect')
	  .data(LegendOptions)
	  .enter()
	  .append("rect")
	  .attr("x", w - 65)
	  .attr("y", function(d, i){ return i * 20;})
	  .attr("width", 10)
	  .attr("height", 10)
	  .style("fill", function(d, i){ return colorscale(i);})
	  ;
	//Create text next to squares
	legend.selectAll('text')
	  .data(LegendOptions)
	  .enter()
	  .append("text")
	  .attr("x", w - 52)
	  .attr("y", function(d, i){ return i * 20 + 9;})
	  .attr("font-size", "11px")
	  .attr("fill", "#737373")
	  .text(function(d) { return d; })
	  ;

  console.log(">> Chart end...");

});
<?php
  }
 
}
