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

    $b_continue = $this->authenticate($score->createdBy);
    if (!$b_continue) {
      return;
    }
  ?>
    <ul>
    <li> Quiz title:  <?php echo $score->quiz_name ?>
    <li> Quiz completed: <?php echo $score->endDate ?>
    <li> User name:  <?php echo $score->user_name ?>
    </ul>
  <?php

  ?>
    <div id=jlq-score-body ><div id=jlq-score-chart ></div></div>

    <script src="<?php echo plugins_url('js/radar-charts-d3.js',
        JUXTALEARN_QUIZ_REGISTER_FILE) ?>"></script>
    <script>
    <?php $this->print_spider_javascript($score) ?>
    </script>

    <table id=jlq-score-table >
      <tr><th>Stumbling block</th> <th>Questions</th> <th>Scores</th></tr>

<?php foreach ($score->stumbling_blocks as $sb_id => $sb): ?>
      <tr><td>SB <?php echo $sb_id ?></td> <td><?php echo $sb['qs'] ?></td> <td><?php echo $sb['score'] ?></td></tr>
<?php endforeach; ?>
    </table>

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
    # http://dl.dropbox.com/u/3203144/chartjs.html
    # http://bl.ocks.org/nbremer/6506614#RadarChart.js
    ?>
(function () {

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
		{axis: "SB <?php echo $sb_id ?>", value: <?php echo $sb['score'] ?> },
<?php endforeach; ?>
		{}
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

})();
    <?php
  }
 
}
