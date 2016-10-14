<!doctype html>
<html>
	<head>
		<title>My experiment</title>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.3.10/seedrandom.min.js"></script>
		<script src="jspsych-5.0.3/jspsych.js"></script>
		<script src="jspsych-5.0.3/plugins/jspsych-text.js"></script>
		<script src="jspsych-5.0.3/plugins/jspsych-single-stim.js"></script>
		<script src="jspsych-5.0.3/plugins/jspsych-vsl-grid-scene.js"></script>
		<script src="jspsych-5.0.3/plugins/jspsych-catlearn-categorize.js"></script>
		<script src="Util.js"></script>
		<link href="jspsych-5.0.3/css/jspsych.css" rel="stylesheet" type="text/css"></link>
	</head>
	<body>
	</body>
	<script>

	// Generate a seed for the random number generator
	<?php date_default_timezone_set('MST'); ?>

	var seed = <?php echo json_encode(date("Ymdhis")); ?>;

	Math.seedrandom(seed);

	function getRandomIntInclusive(min, max) {
	  min = Math.ceil(min);
	  max = Math.floor(max);
	  return Math.floor(Math.random() * (max - min + 1)) + min;
	}
	// Some experiment parameters.
	var numTestTrials = 10; // Should be even
	var numLearnTrials = 4 * numTestTrials;
	var fam1Key = 'e';
	var fam1KeyCode = 69;
	var fam2Key = 'i';
	var fam2KeyCode = 73;
	var imSize = [100, 100];

	// Get the file names of the families

	var fam1Files = <?php echo json_encode(glob('./Family1/*.jpg')); ?>

	var fam2Files = <?php echo json_encode(glob('./Family2/*.jpg')); ?>

	console.log("fam1Files and fam2Files")
	console.log(fam1Files);
	console.log(fam2Files);

	// Choose a number of stimuli for the test trials, and store the rest.
	var fam1TestFiles = rndSelect(fam1Files, numTestTrials/2);
	var fam2TestFiles = rndSelect(fam2Files, numTestTrials/2);

	var fam1TrialFiles = [];
	var fam2TrialFiles = [];
	for(i=0; i<fam1Files.length; i++) {
		var tempFl = fam1Files[i];
		if(!contains.call(fam1TestFiles, tempFl)) {
			fam1TrialFiles.push(tempFl);
		}
	}
	for(i=0; i<fam2Files.length; i++) {
		var tempFl = fam2Files[i];
		if(!contains.call(fam2TestFiles, tempFl)) {
			fam2TrialFiles.push(tempFl);
		}
	}

	// Simultaneously shuffle them and get the right number for the experiment
	fam1TrialFiles = rndSelect(fam1TrialFiles, numLearnTrials/2);
	fam2TrialFiles = rndSelect(fam2TrialFiles, numLearnTrials/2);

	// Randomly choose the order of the test trials.
	var testTrialFiles = fam1TestFiles.concat(fam2TestFiles);
	testTrialFiles = rndSelect(testTrialFiles, testTrialFiles.length);
	var testTrialSet = [];
	for (i=0; i<numTestTrials; i++) {
		var curFile = testTrialFiles.pop();
		if (curFile.search('Family1') > -1) {
			testTrialSet.push([curFile, 'Family 1', fam1Key, fam1KeyCode]);
		} else {
			testTrialSet.push([curFile, 'Family 2', fam2Key, fam2KeyCode]);
		}
	};

	console.log('\n testTrialSet')
	console.log(testTrialSet);
	console.log('Finished shuffling');

	/*
		// Choose a condition
		switch (getRandomIntInclusive(1, 2)) {
			case 1:
				var cond = 'same';
			case 2:
				var cond = 'different';
			default:
				throw('Not a valid condition choice.');
		}
	*/
		var cond = 'same';
	// Timeline initialization. These parameters are applied to every trial.
	var block = {
		type: 'catlearncategorize',
		is_html: true,
		timeline: [],
		timing_post_trial: 0
	};

	for(i=1; i<=numTestTrials; i++) {
	// set up the timeline.
		switch (cond) {
			case 'same':

				// Set up the timelines

					console.log(i);
					// Make 2 learning trials for each family, and a test trial.
					var fam1T1Stim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
						[
							[fam1TrialFiles.pop(), fam1TrialFiles.pop()]
						], imSize);
					var fam1T2Stim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
						[
							[fam1TrialFiles.pop(), fam1TrialFiles.pop()]
						], imSize);
					var fam2T1Stim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
						[
							[fam2TrialFiles.pop(), fam2TrialFiles.pop()]
						], imSize);
					var fam2T2Stim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
						[
							[fam2TrialFiles.pop(), fam2TrialFiles.pop()]
						], imSize);

					// TODO: add data to the learn trials to indicate the family.
					var learnTrials = {
						choices: [fam1Key, fam2Key],
						show_stim_with_feedback: true,
						prompt: 'Press the "' + fam1Key + '" key if these are from family 1 ' +
						'and the "' + fam2Key + '" key if they are from family 2.',
						correct_text: 'Correct! These are from %ANS%.',
						incorrect_text: 'Wrong! These are from %ANS%.',
						timing_feedback_duration: 2000,
						timeline: [
							// Family 1 trials
							{
								stimulus: fam1T1Stim,
								key_answer: fam1KeyCode,
								text_answer: 'Family 1'
							},
							{
								stimulus: fam1T2Stim,
								key_answer: fam1KeyCode,
								text_answer: 'Family 1'
							},
							// Family 2 trials
							{
								stimulus: fam2T1Stim,
								key_answer: fam2KeyCode,
								text_answer: 'Family 2'
							},
							{
								stimulus: fam2T2Stim,
								key_answer: fam2KeyCode,
								text_answer: 'Family 2'
							}
						],
						randomize_order: true
					}
				break;
			case 'different':

				break;
			default:
				throw "Not a valid condition in building stuff."
		}
		// Make the test trial.
		// TODO: Add data to the test trial to indicate the family.
		var curTrial = testTrialSet.pop();
		var testTrialStim = jsPsych.plugins['vsl-grid-scene'].generate_stimulus(
			[
				[curTrial[0]]
			], imSize);
		var testTrial = {
			stimulus: testTrialStim,
			key_answer: curTrial[3],
			choices: [fam1Key, fam2Key],
			timing_feedback_duration: 0,
			prompt: 'Is this from Family 1 (Press the "' + fam1Key + '" key), ' +
			'or from Family 2 (Press the "' + fam2Key + '" key)?'
		};
		// Add them to the block timeline.
		block.timeline.push(learnTrials);
		block.timeline.push(testTrial);
	} // end for loop

	// Add the condition to the data
	console.log(cond);

	var end_block = {
	  type: 'text',
	  text: 'Reached the end! Press space to move on.',
		cont_key: ['space']
	};

	var timeline = [];
	//TODO: Make an instruction block
	//timeline.push(instr_block);
	timeline.push(block);
	timeline.push(end_block);
	console.log(timeline);

	jsPsych.init({
	  timeline: timeline,
	  on_finish: function() {
	    jsPsych.data.displayData();
	  }
	});

	</script>
</html>
