<?php
/**
 * Wordpress shortcode to visualize JuxtaLearn quiz scores.
 *
 * Usage:
 *   [quiz_score] - With `my-page/{SCORE ID}/`
 *   [quiz_score id={SCORE_ID}]
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

  public function __construct() {
    add_shortcode(self::SHORTCODE, array(&$this, 'quiz_score_shortcode'));
  }


  public function quiz_score_shortcode($attrs, $content = '', $name) {
    $jlq_score_id = $this->url_parse_id($attrs);

    $score = $this->model_get_score($jlq_score_id, $offset = 1);
    $permission = isset($score->permission) ? $score->permission : NULL;

    $b_continue = $this->auth_permitted($score->createdBy, $permission, $auth_reason);
    if (!$b_continue) {
      return;
    }

    $offset = $score->offset;
  ?>

    <!--AUTH: <?php echo $auth_reason ?> -->
    <figure aria-labelledby="jlq-score-caption" role="img">
    <figcaption id="jlq-score-caption">
    <div>Spider or radar chart of cumulative quiz scores versus stumbling blocks,
      for the <a href="<?php echo $score->tricky_topic_url ?>"><?php
         echo $score->tricky_topic_title ?></a> tricky topic. <small>(Offset: <?php echo $offset ?>)</small></div>

    <?php if (count($score->stumbling_blocks) < 1): ?>
    <p class="error no-sbs">ERROR. Sorry! I couldn't get any stumbling blocks. A bug maybe? :( <?php if (isset($score->warning)):?><small>Warning: <?php echo $score->warning ?></small></p>

    <?php elseif (count($score->stumbling_blocks) < 3): ?>
    <small class="warn low-sbs">(Note: we have less than 3 stumbling blocks, so the chart won't look great!)</small>
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
            <div id="loading" class="jl-chart-loading">Loading chart...</div>
        </div>
    </div>
    </figure>

    <ul id=jlq-score-meta >
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

    <script src=
    "<?php echo plugins_url('js/radar-charts-d3.js', JUXTALEARN_QUIZ_REGISTER_FILE) ?>"
    ></script>
    <script>
    <?php $this->print_spider_javascript($score) ?>
    </script>

    <script>
    document.documentElement.className += " shortcode-<?php echo self::SHORTCODE ?>";
    </script>
    <?php
  }


  protected function model_get_score($score_id, $offset) {
    $model = new JuxtaLearn_Quiz_Model();
    $score = $model->get_score($score_id, $offset);
    if (!$score) {
      $this->error_404('Invalid score ID: '. $score_id);
    }
    return $score;
  }

  protected function print_spider_javascript($score) {
    # http://bl.ocks.org/nbremer/6506614#RadarChart.js

    $max = (float) $score->maximum_score;
    $limit = count($score->stumbling_blocks);
    $count = 0;
    ?>
jQuery(function ($) {

  if (!window.d3) {
    return;
  }

  console.log(">> Chart start - d3 exists.");

  $(".jl-chart-loading").hide();

  var max_score = <?php echo $max ?>;

  var w = 500,
	h = 500;

var colorscale = d3.scale.category10();

//Legend titles
var LegendOptions = [ 'Smartphone', <?php /*'Quiz: <?php
   #echo $score->quiz_name ?>/ <?php
   #echo $score->user_name ?>/ <?php
   #echo $score->endDate ?>',*/ ?> 'Tablet'];

//Data
var d = [
		  [
			//{axis:"Email",value:0.59},
<?php foreach ($score->stumbling_blocks as $sb_id => $sb): ?>
		{axis: "<?php echo $sb['sb'] .' (SB:'. $sb_id .')' ?>", value: <?php
		    echo $sb['score'] / $max ?> }<?php $count++; echo $count < $limit ? ',':''; ?>

<?php endforeach; ?>
		  ],[
		  ]
		];

//Options for the Radar chart, other than default
var mycfg = {
  w: w,
  h: h,
  maxValue: 0.6,
  levels: 6,
  ExtraWidthX: 300
}

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
	.text("What % of owners use a specific service in a week");

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
