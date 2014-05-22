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
  const DEF_DIVISOR = 1;   //Was: 'max_score'
  const DEF_OFFSET  = 0.4; //Was: 1;

  protected $offset;
  protected $divisor;
  protected $chart_size; //pixels
  protected $chart_intervals;
  protected $font_size = 11;  //Was: 10 (10px); 12;
  protected $debug = FALSE;


  public function __construct() {
    add_shortcode(self::SHORTCODE, array(&$this, 'quiz_score_shortcode'));
  }

  protected function set_score_options() {
    $this->offset = floatval($this->_get('offset', self::DEF_OFFSET));
    $this->divisor = $this->_get('divisor', self::DEF_DIVISOR);
    $this->chart_size = absint($this->_get('chartsize', 500));
    $this->chart_intervals = absint($this->_get( 'intervals', 6 ));
    $this->debug = (bool) $this->_get( 'debug' );
  }

  public function quiz_score_shortcode($attrs, $content = '', $name) {
    //Was: $jlq_score_id
    $sq_score_id = $this->url_parse_id($attrs);
    $this->set_score_options();

    $score = $this->model_get_score($sq_score_id, $this->offset);
    $permission = isset($score->permission) ? $score->permission : NULL;

    $b_continue = $this->auth_permitted($score->score_user_id, $permission, $auth_reason);
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

    ob_start();

    $this->print_score_markup(array($score));
    ?>

    <script src=
    "<?php echo plugins_url('js/radar-charts-d3.js', JUXTALEARN_QUIZ_REGISTER_FILE) ?>"
    ></script>
    <script>
    <?php $this->print_spider_javascript(array($score)) ?>
    </script>

<?php
    $this->print_utility_javascripts($score);

    return ob_get_clean();
  }


  protected function print_score_markup($all_scores, $notes = NULL) {

    $score = $all_scores[0];
    $offset = $score->offset;
  ?>
    <div id=jlq-score >
    <style> .jlq-score-bn { display: none; } </style>

    <figure id=jlq-score-figure aria-labelledby="jlq-score-caption" role="img">
    <figcaption>
    <h2 id="jlq-score-caption"><?php echo sprintf( __(
        'Radar chart for the <a %s>%s tricky topic</a> quiz',
        self::LOC_DOMAIN), "href='$score->tricky_topic_url'", $score->tricky_topic_title) ?>
    <small>(offset: <?php echo $offset ?>)</small></h2>
<!--Spider or radar chart of cumulative quiz scores versus stumbling blocks, for the <a %s>%s tricky topic</a>. -->
    <?php /*Was: if ($notes) ..*/ ?>

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
    <li> Quiz title:   <a href="<?php echo $score->quiz_url ?>"
      ><?php echo $score->quiz_name ?></a>
    <li> Tricky Topic: <a href="<?php echo $score->tricky_topic_url ?>"><?php
         echo $score->tricky_topic_title ?></a>
    <li> Completion(s):  <?php echo count($all_scores) ?>
    <li> Who completed?
    <?php foreach ($all_scores as $sc): ?>
      <em title="<?php echo $sc->endDate .' / Score ID: '. $sc->score_id ?>"
          ><?php echo $sc->user_name ?></em>,
    <?php endforeach; ?>
    </ul>

    <?php if ($notes): ?>
      <p class=notes ><?php echo $notes ?></p>
    <?php endif; ?>

    <table id=jlq-score-table >
      <thead><tr><th>Stumbling block</th> <th>Questions</th> <th>Scores</th></tr></thead>

<?php foreach ($score->stumbling_blocks as $sb_id => $sb): ?>
      <tr><td title="SB <?php echo $sb_id ?>"><?php echo $sb['sb'] ?><i> (SB:<?php echo $sb_id ?>)</i></td>
        <td class=qn ><ul><li><?php echo implode(' <li>', $sb['qs']) ?></ul></td>
        <td class=sc >
        <?php foreach ($all_scores as $sc):
            $scb = $sc->stumbling_blocks[$sb_id]; ?>
            <em title="<?php echo $sc->user_name ?>"><?php echo $scb['score'] - $offset ?></em>,
        <?php endforeach; ?></td></tr>
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


  protected function print_spider_javascript($the_scores, $is_personal = TRUE) {
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
    if ($this->chart_intervals <= 0) {
      $this->chart_intervals = 6;
    }
    $meta = json_encode(array(
      'divisor' => $divisor,
      'max_score' => $max_score,
      'offset' => $this->offset,
      'format' => $format,
      'font_size' => $this->font_size,
      'intervals' => $this->chart_intervals,
    ));

    ?>
/*jslint devel: true, vars: true, white:true, unparam:true, indent:2 */
/*global jQuery:false, window:false, d3:false, RadarChart:false */

jQuery(function ($) {

  'use strict';

  if (!window.d3) {
    return;
  }

  console.log(">> Chart start - d3 exists. Meta-data:", <?php echo $meta ?> );

  $(".jl-chart-loading").hide();

  var width = <?php echo $this->chart_size ?>,
	height = <?php echo $this->chart_size ?>;

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

//Data (Was: "var d..")
var chart_data = [
<?php foreach ($the_scores as $j => $score): ?>
<?php
    $sb_limit = count($score->stumbling_blocks);
    $sb_count = 0;
    $debug = $this->debug;
?>
		[
    <?php foreach ($score->stumbling_blocks as $sb_id => $sb): ?>
		{axis: "<?php echo $sb['sb'] . ($debug ? " (SB:$sb_id)" :'') ?>", value: <?php
		    echo $sb['score'] / $divisor ?> }<?php $sb_count++; echo $sb_count < $sb_limit ? ',':''; ?>

    <?php endforeach; ?>
		]<?php echo $j < ($num_scores - 1) ? ',':'' ?>

<?php endforeach; ?>
];

//Options for the Radar chart, other than default
var mycfg = {
  w: width,
  h: height,
  maxValue: <?php echo $max_score //0.6 ?>,
  format: '<?php echo $format ?>',
  levels: <?php echo $this->chart_intervals ?>,
  fontSize: <?php echo $this->font_size ?>,  //Was: 10 (10px)
  ExtraWidthX: 300
};

//Call function to draw the Radar chart
//Will expect that data is in %'s
RadarChart.draw("#jlq-score-chart", chart_data, mycfg);

////////////////////////////////////////////
/////////// Initiate legend ////////////////
////////////////////////////////////////////

var svg = d3.select('#jlq-score-body')
	.selectAll('svg')
	.append('svg')
	.attr("width", width + 300)
	.attr("height", height)
	;
//Create the title for the legend
var text = svg.append("text")
	.attr("class", "title")
	.attr('transform', 'translate(130,0)')  //Was: (90,0)
	.attr("x", width - 70)
	.attr("y", 10)  //10
	.attr("font-size", (<?php echo $this->font_size ?> + 2) + "px")  //Was: 12px
	.attr("fill", "#404040")
	.text("<?php echo $is_personal ? __('Your latest quiz attempt', self::LOC_DOMAIN) :
		sprintf(__('%s students completed the quiz', self::LOC_DOMAIN), $num_scores) ?>");

//Initiate Legend
var legend = svg.append("g")
	.attr("class", "legend")
	.attr("height", 100)
	.attr("width", 200)
	.attr('transform', 'translate(130,<?php echo $this->font_size + 10 ?>)')  //Was: (90,20)
	;
	//Create colour squares
	legend.selectAll('rect')
	  .data(LegendOptions)
	  .enter()
	  .append("rect")
	  .attr("x", width - 65)
	  .attr("y", function(d, i){ return i * 20;})
	  .attr("width", 10)
	  .attr("height", <?php echo $this->font_size ?>)
	  .style("fill", function(d, i){ return colorscale(i);})
	  ;
	//Create text next to squares
	legend.selectAll('text')
	  .data(LegendOptions)
	  .enter()
	  .append("text")
	  .attr("x", width - 52)
	  .attr("y", function(d, i){ return i * 20 + 9;})
	  .attr("font-size", (<?php echo $this->font_size ?> + 1) + "px")  //Was: 11px
	  .attr("fill", "#737373")
	  .text(function(d) { return d; })
	  ;

  console.log(">> Chart end...");

});
<?php
  }


  protected function print_utility_javascripts($score) {
    $sc = is_array($score) ? $score[0] : $score;

    if ($this->debug): ?>
    <script>
    var JLQ_score_data = <?php echo json_encode($score) ?>;
    window.console && console.log(">> Score data:", JLQ_score_data);
    </script>
    <?php endif; ?>

    <script>
    jQuery(function ($) {
      var $meta = $(".simple-embed #jlq-score-meta");
      $("#jlq-score .jlq-score-bn").click(function () {
        $meta.toggle();
      });
      $meta.hide();

      $("title").html( $("title").html().replace(/Page \d+/, <?php
          echo json_encode("$sc->quiz_name [Quiz ID: $sc->quiz_id]") ?>) );
    });
    </script>

    <script>
    document.documentElement.className += " shortcode-<?php echo self::SHORTCODE ?>";
    </script>
<?php
  }

}
