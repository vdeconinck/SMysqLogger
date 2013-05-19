function drawDial(options) {

	var renderTo = options.renderTo,
		value = options.value,
		centerX = options.centerX,
		centerY = options.centerY,
		min = options.min,
		max = options.max,
		minAngle = options.minAngle,
		maxAngle = options.maxAngle,
		tickInterval = options.tickInterval,
		ranges = options.ranges;
		
	var renderer = new Highcharts.Renderer(
		document.getElementById(renderTo),
		400,
		300
	);


	// internals
	var angle, pivot;

	function valueToAngle(value) {
		return (maxAngle - minAngle) / (max - min) * value + minAngle;
	}

	function setValue(value) {
		// the pivot
		angle = valueToAngle(value);
		
		var path = [
			 'M',
			 centerX, centerY,
			 'L',
			 centerX + 110 * Math.cos(angle), centerY + 110 * Math.sin(angle)
		 ];
		
		if (!pivot) {
			pivot = renderer.path(path)
			.attr({
				stroke: 'black',
				'stroke-width': 3
			})
			.add();
		} else {
			pivot.attr({
				d: path
			});
		}
	}

	// background area
	renderer.arc(centerX, centerY, 180, 0, minAngle, maxAngle)
		.attr({
			fill: {
				linearGradient: [0, 0, 0, 200],
				stops: [
					[0, '#FFF'],
					[1, '#DDD']
				]
			},
			stroke: 'silver',
			'stroke-width': 1
		})
		.add();


	// ranges
	$.each(ranges, function(i, rangesOptions) {
		renderer.arc(
			centerX,
			centerY,
			170,
			150,
			valueToAngle(rangesOptions.from),
			valueToAngle(rangesOptions.to)
		)
		.attr({
			fill: rangesOptions.color
		})
		.add();
	});

	// ticks
	for (var i = min; i <= max; i += tickInterval) {
		
		angle = valueToAngle(i);
		
		// draw the tick marker
		renderer.path([
				'M',
				centerX + 170 * Math.cos(angle), centerY + 170 * Math.sin(angle),
				'L',
				centerX + 150 * Math.cos(angle), centerY + 150 * Math.sin(angle)
			])
			.attr({
				stroke: 'silver',
				'stroke-width': 2
			})
			.add();
		
		// draw the text
		renderer.text(
				i,
				centerX + 130 * Math.cos(angle),
				centerY + 130 * Math.sin(angle)
			)
			.attr({
				align: 'center'
			})
			.add();
		
	}

	// the initial value
	setValue(value);

	// center disc
	renderer.circle(centerX, centerY, 10)
		.attr({
			fill: '#4572A7',
			stroke: 'black',
			'stroke-width': 1
		})
		.add();

	return {
		setValue: setValue
	};

}
