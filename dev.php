<?php
$client_ip = $_SERVER ['REMOTE_ADDR'];
$browser = strpos ( $_SERVER ['HTTP_USER_AGENT'], "iPhone" );
if ($browser == true) {
	$browser = 'iphone';
}
?>
<!DOCTYPE html>
<html>
<head>
<title>SEER Treemap</title>
<?php
require "../../preload.php";
echo $header;
?>
<!-- D3 and other req. libraries -->
<script src="https://d3js.org/d3.v3.min.js"></script>

<script src="https://code.jquery.com/jquery-3.1.1.min.js">
    </script>
<script src="https://use.fontawesome.com/bb47d90cfb.js"></script>
<script src="http://code.jquery.com/ui/1.12.1/jquery-ui.min.js"
	integrity="sha256-VazP97ZCwtekAsvgPBSUwPFKdrwD3unUfSGVYrahUqU="
	crossorigin="anonymous"></script>
<script
	src="https://cdn.rawgit.com/vast-engineering/jquery-popup-overlay/1.7.13/jquery.popupoverlay.js"></script>
<!-- fonts -->
<link href="https://fonts.googleapis.com/css?family=Kameron"
	rel="stylesheet">
<script src="./graph.js"></script><link href="vis-style.css" rel="stylesheet" type="text/css">
<meta charset="utf-8">
<style>
body {
	background-color: #ffffff;
}

#inject-treemap {
	width: 1100px;
	height: 600px;
	background: #ddd;
	position: absolute;
	left: 0;
	right: 0;
	top: 10px;
	margin: auto;
}

text {
	pointer-events: none;
}

.grandparent text {
	font-weight: bold;
}

rect {
	fill: none;
	stroke: #fff;
}

rect.parent, .grandparent rect {
	stroke-width: 2px;
}

.grandparent rect {
	fill: #001A57;
}

.grandparent text {
	fill: #ffffff;
	font-size: 12px;
}

.grandparent:hover rect {
	fill: #ffffff;
}

.grandparent:hover text {
	fill: #000;
}

.children rect.parent, .grandparent rect {
	cursor: pointer;
}

.children rect.parent {
	fill: #bbb;
	fill-opacity: .5;
}

.children:hover rect.child {
	stroke: #bbb;
}

#footer-author {
	position: fixed;
	bottom: 0;
	left: 0;
	width: 100%;
	z-index: 0;
}

/* basic positioning */
.legend { list-style: none; }
.legend li { float: left; margin-right: 10px; }
.legend span { border: 1px solid #ccc; float: left; width: 12px; height: 12px; margin: 2px; }


</style>
</head>

<body>
	<div id="container">
		<div id="inject-treemap"></div>
		<div id ="inject-graph"></div>
		<div id="client-info-container">
			<p>Connected from IP: <?php echo $client_ip;?></p>
		</div>
		<div id="footer-author">
			<footer class="w3-container w3-padding-16 w3-center w3-opacity">
				<div class="w3-xlarge w3-padding-8">
					<a href="#" class="w3-hover-text-indigo"
						onClick="document.location.href=getFacebookUrl();"><i
						class="fa fa-facebook-official"></i></a> <a href="/vis"
						class="w3-hover-text-teal" target="_blank"><i
						class="fa fa-pie-chart"></i></a>
				</div>
				<p>
					Handcrafted by Jaret Karnuta <a
						href="https://www.linkedin.com/in/jaretkarnuta"
						class="w3-medium w3-hover-text-red" target="_blank"><i
						class="fa fa-linkedin"></i></a> <a
						href="https://www.github.com/jaretk"
						class="w3-medium w3-hover-text-blue" target="_blank"><i
						class="fa fa-github"></i></a>, CCLCM class of 2021
				</p>
			</footer>
		</div>
	</div>
	<!-- code to generate reference-bar -->

	<script>
     $(document).ready(function(){
        $('#my_popup').popup({
            onopen: function() {
            $("#my_popup").load("about.html");
                 console.log($("#my_popup").html());
            }
        });
     });
     </script>
	<!-- main logic -->
	<script>

var margin = {top: 60, right: 0, bottom: 0, left: 0},
    width = 1100,
    height = 600 - margin.top - margin.bottom,
    button = 200,
    formatNumber = d3.format(",d"),
    transitioning,
    graphOpen,
    sep = " -> ";

var colorSet = new Set();
    
var loadingjson = false,
	json_temporal;
    
var temporal_data = "data/seer-data-temporal.json"

var x = d3.scale.linear()
    .domain([0, width])
    .range([0, width]);

var y = d3.scale.linear()
    .domain([0, height])
    .range([0, height]);

var treemap = d3.layout.treemap()
    .children(function(d, depth) { return depth ? null : d._children; })
    .sort(function(a, b) { return a.value - b.value; })
    .ratio(height / width * 0.5 * (1 + Math.sqrt(5)))
    .round(false);

var svg = d3.select("#inject-treemap")
   	.append("svg")
    .attr("width", width + margin.left + margin.right)
    .attr("height", height + margin.bottom + margin.top)
    .style("margin-left", -margin.left + "px")
    .style("margin.right", -margin.right + "px")
  	.append("g")
    .attr("transform", "translate(" + margin.left + "," + margin.top + ")")
    .style("shape-rendering", "crispEdges");
	
var grandparent = svg.append("g")
    .attr("class", "grandparent");

var grandparent2 = svg.append("g")
	.attr("class", "grandparent")
	.attr("id", "current-selection");

var graphButton = svg.append("g")
	.attr("class", "grandparent")
	.attr("id", "side-button");

graphButton.append("rect")
.attr("y",-margin.top)
.attr("x", width - button)
.attr("width", button)
.attr("height", margin.top);
	
graphButton.append("text")
	.attr("class", "button-text")
	.attr("x", width - button + 15)
	.attr("y", -margin.top/2)
	.attr("style", "margin:0 auto")
	.text("Temporal Mortality Data");
	
grandparent.append("rect")
    .attr("y", -margin.top)
    .attr("width", width-button)
    .attr("height", margin.top/2);

grandparent.append("text")
    .attr("x", 6)
    .attr("y", 6 - margin.top)
    .attr("dy", ".75em");

grandparent2.append("rect")
.attr("y", -margin.top/2)
.attr("width", width-button)
.attr("height", margin.top/2);

grandparent2.append("text")
.attr("x", 6)
.attr("y", (6 - margin.top)/2)
.attr("dy", ".75em");


//TODO: ADD SPINNER TO GRAPH BUTTON
d3.json(temporal_data, function(root){
	loadingjson = false;
	json_temporal = root;
	console.log('done loading');
  });

var legend = d3.select("#inject-treemap").append("ul")
.attr("class","legend")
.attr("id", "color-legend");

d3.json("data/seer-data.json", function(root) {
  initialize(root);
  accumulate(root);
  layout(root);
  display(root);

  function initialize(root) {
    root.x = root.y = 0;
    root.dx = width;
    root.dy = height;
    root.depth = 0;
  }

  // Aggregate the values for internal nodes. This is normally done by the
  // treemap layout, but not here because of our custom implementation.
  // We also take a snapshot of the original children (_children) to avoid
  // the children being overwritten when when layout is computed.
  function accumulate(d) {
    return (d._children = d.children)
        ? d.value = d.children.reduce(function(p, v) { return p + accumulate(v); }, 0)
        : d.value;
  }

  // Compute the treemap layout recursively such that each group of siblings
  // uses the same size (1×1) rather than the dimensions of the parent cell.
  // This optimizes the layout for the current zoom state. Note that a wrapper
  // object is created for the parent node for each group of siblings so that
  // the parent’s dimensions are not discarded as we recurse. Since each group
  // of sibling was laid out in 1×1, we must rescale to fit using absolute
  // coordinates. This lets us use a viewport to zoom.
  function layout(d) {
    if (d._children) {
      treemap.nodes({_children: d._children});
      d._children.forEach(function(c) {
        c.x = d.x + c.x * d.dx;
        c.y = d.y + c.y * d.dy;
        c.dx *= d.dx;
        c.dy *= d.dy;
        c.parent = d;
        layout(c);
      });
    }
  }

  function display(d) {
    grandparent
        .datum(d.parent)
        .on("click", transition)
      	.select("text")
        .text(name(d));
    
    graphButton.datum(d).on("click", callGraph);
    
    grandparent2.datum(d.parent)
    .on("click", transition)
    .select("text").text(function(){
    	for (i = 0; i < d._children.length; i++){
			var curr = d._children[i].name;
			if (curr == "URINARY"){
				return "Type of Primary Cancer (Archetype)";
			}
			if (curr == "0-10"){
				return "Age at Diagnosis (AGE_DX)";
			}
			if (curr == "White"){
				return "Race of patient (RACE)";
			}
			if (curr == "Male"){
				return "Binary sex of patient (SEX)";
			}
			if (curr == "In Situ"){
				return "Historical SEER Stage (HST_STGA)";
			}
			if (curr == "Dead"){
				return "Status of life after treatment ended (death caused by cancer)";
			}
    	}
    	return "Unknown";
    });

    var g1 = svg.insert("g", ".grandparent")
        .datum(d)
        .attr("class", "depth");

    var g = g1.selectAll("g")
        .data(d._children)
      .enter().append("g");

    g.filter(function(d) { return d._children; })
        .classed("children", true)
        .on("click", transition);

    g.selectAll(".child")
        .data(function(d) { return d._children || [d]; })
      	.enter().append("rect")
        .attr("class", "child")
        .call(rect);

    //color
    g.selectAll(".child")
	.style("fill", function(d){
		var color = colorBox(d);
		var dn = d.name;
		
		if (!colorSet.has(dn)){
	  		colorSet.add(dn);
  			console.log(d);
  			legend.append("li")
  				.text(dn)
  				.append("span").attr("style", "background-color:"+color+";");
		}
		return color;
	});

    g.append("rect")
        .attr("class", "parent")
        .call(rect)
      	.append("title")
        .text(function(d) {
            return d.name + " (Obs: "+formatNumber(d.value)+")"; 
            });

    g.append("text")
        .attr("dy", ".75em")
        .text(function(d) {
            if (d.name == "Alive" || d.name == "Dead"){ 
            return d.name + "(Obs: "+formatNumber(d.value)+")"; 
            }
            return d.name;
            })
        .call(text);


    function callGraph(d){
  	if(graphOpen || !d) return;
	//graphOpen = true;
	drawGraph(name(d).split(sep), $("#inject-graph"));
    }

    function transition(d) {
      if (transitioning || !d) return;
      transitioning = true;

      var g2 = display(d),
          t1 = g1.transition().duration(750),
          t2 = g2.transition().duration(750);


	  colorSet = new Set();//refresh set
	  legend.selectAll("*").remove();
      
      // Update the domain only after entering new elements.
      x.domain([d.x, d.x + d.dx]);
      y.domain([d.y, d.y + d.dy]);

      // Enable anti-aliasing during the transition.
      svg.style("shape-rendering", null);

      // Draw child nodes on top of parent nodes.
      svg.selectAll(".depth").sort(function(a, b) { return a.depth - b.depth; });

      // Fade-in entering text.
      g2.selectAll("text").style("fill-opacity", 0);

      // Transition to the new view.
      t1.selectAll("text").call(text).style("fill-opacity", 0);
      t2.selectAll("text").call(text).style("fill-opacity", 1);
      t1.selectAll("rect").call(rect);
      t2.selectAll("rect").call(rect);

      // Remove the old node when the transition is finished.
      t1.remove().each("end", function() {
        svg.style("shape-rendering", "crispEdges");
        transitioning = false;
      });
    }

    return g;
  }
  
  function text(text) {
    text.attr("x", function(d) { return x(d.x) + 6; })
        .attr("y", function(d) { return y(d.y) + 6; });
  }

  function rect(rect) {
    rect.attr("x", function(d) { return x(d.x); })
        .attr("y", function(d) { return y(d.y); })
        .attr("width", function(d) { return x(d.x + d.dx) - x(d.x); })
        .attr("height", function(d) { return y(d.y + d.dy) - y(d.y); });
  }

  function name(d) {
    return d.parent
        ? name(d.parent) + sep + d.name
        : d.name;
  }

	function drawGraph(query, inject){
		console.log(query);
	  }

	  function colorBox(d) {
			var dn = d.name;
			if (dn == "Male"){
		  		return '#89cff0';
			}
			else if (dn == "Female"){
		  		return '#ffb6c1';
			}
			if (dn == "Dead"){
		  		return "#E15E5E";
			}
			if (dn=="Alive"){
		  		return "#33cc66";
			}
			if(dn=='Unknown')return'#7f957c';
			
			if(dn=="61-70"){return '#ff3399';}
			if(dn=="51-60"){return '#5EA8E1';}
			if(dn=="71-80"){return '#E0F0F9';}
			if(dn=="41-50"){return '#95D4FD';}
			if(dn=="81-90"){return '#85F2B8';}
			if(dn=="31-40"){return '#D795FD';}
			if(dn=="21-30"){return '#E1B95E';}
			if(dn=="121-130"){return '#7e3bb8';}
			if(dn=="111-120")return '#046774';
			if(dn=='101-110')return'#66f133';
			if(dn=='11-20')return'#8b2431';
			if(dn=='0-10')return'#28d0e8';
			if(dn=='91-100')return'#d55c4f';

			if(dn=="White"){return '#D3D3D3';}
			if(dn=="Black"){return '#696969';}
			if(dn=="Asian / Pacific Islander"){return '#A9A9A9';}
			if(dn.includes("Native")){return '#000000';}
			if(dn.includes("Other"))return '#708090';
			
			if(dn=="In Situ"){return '#DFFFA5';}
			if(dn=="Localized"){return '#FFFF7E';}
			if(dn=="Regional"){return '#FFC469';}
			if(dn=="Distant"){return '#FF8C69';}
			if(dn.includes("Prostate"))return "#ff3399";
			if(dn=="Unstaged"){return '#28d0e8';}
			}
});

</script>
</body>
</html>