<!doctype html>
<html>
<head>
	<title>My experiment</title>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.4.2/seedrandom.min.js"></script>
	<script src="jspsych-5.0.3/jspsych.js"></script>
	<script src="jspsych-5.0.3/plugins/jspsych-text.js"></script>
	<script src="jspsych-5.0.3/plugins/jspsych-single-stim.js"></script>
	<script src="jspsych-5.0.3/plugins/jspsych-catlearn-vsl-grid-scene.js"></script>
	<script src="jspsych-5.0.3/plugins/jspsych-catlearn-categorize.js"></script>
	<script src="Util.js"></script>
	<link href="jspsych-5.0.3/css/jspsych.css" rel="stylesheet" type="text/css"></link>
</head>
<body>
</body>
<script>

// Generate a seed for the random number generator
var d = new Date();
var pid = d.getTime();

Math.seedrandom(pid);

// Some variable declarations
var feedbackTrial, f1T1Fl, f1T2Fl, f2T1Fl, f2T2Fl, fam1T1Stim, fam1T2Stim;
var fam2T1Stim, fam2T2Stim, learnTrials, numRight;

// Get the condition
var condition = 'random';
var cond = condition == 'random' ? rndSelect(['match', 'contrast'], 1)[0] : condition;
// Some experiment parameters.
var numTestTrials = 10; // Should be even; also represents the maximum trials
var numLearnTrials = 4 * numTestTrials;
var minTrials = 20;
var criterion = .95;
var critMet	= false;



// Some image information for stimuli when building the blocks.
var imSize = [
	[[300, 300], [300, 300]]
];
var feedSize = [
	[[300, 300], [300, 300]],
	[[300, 40], [300, 40]]
];

// REVIEW: Do we care about counterbalancing key assignments? YES
var fam1Key =  rndSelect(['e', 'i'], 1)[0];
var fam1KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(fam1Key);
var fam2Key = fam1Key == 'e' ? 'i' : 'e';
var fam2KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(fam2Key);
var fam1Name = rndSelect(['Zoink', 'Kongo'], 1)[0];
var fam2Name = fam1Name == 'Kongo' ? 'Zoink' : 'Kongo';
if (fam1Key == 'e' && fam1Name == 'Zoink') {
	var eKeyFamily = 'Zoink';
	var iKeyFamily = 'Kongo';
} else if (fam1Key == 'e' && fam1Name == 'Kongo') {
	var eKeyFamily = 'Kongo';
	var iKeyFamily = 'Zoink';
} else if (fam1Key == 'i' && fam1Name == 'Zoink') {
	var eKeyFamily = 'Kongo';
	var iKeyFamily = 'Zoink';
} else if (fam1Key == 'i' && fam1Name == 'Kongo') {
	var eKeyFamily = 'Zoink';
	var iKeyFamily = 'Kongo';
}

// Get the file names of the families
var fam1Files = <?php echo json_encode(glob('./Family1/*.jpg')); ?>

var fam2Files = <?php echo json_encode(glob('./Family2/*.jpg')); ?>

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
fam1TrialFiles = rndSelect(fam1TrialFiles, numLearnTrials);
fam2TrialFiles = rndSelect(fam2TrialFiles, numLearnTrials);

// Randomly choose the order of the test trials.
var testTrialFiles = fam1TestFiles.concat(fam2TestFiles);
testTrialFiles = rndSelect(testTrialFiles, testTrialFiles.length);
var testTrialSet = [];
for (i=0; i<numTestTrials; i++) {
	var curFile = testTrialFiles.pop();
	if (curFile.search('Family1') > -1) {
		testTrialSet.push([curFile, 'fam1Name', fam1Key, fam1KeyCode]);
	} else {
		testTrialSet.push([curFile, 'Family 2', fam2Key, fam2KeyCode]);
	}
};

// Save data function
var saveData = function (filename, filedata) {
	$.ajax({
		type: 'post',
		cache: false,
		url: './save_data.php',
		data: {
			filename: filename,
			filedata: filedata
		}
	});
};

// Function to count successes and rates
var successes = function (numTrials) {
	// numTrials is the number of most recent trials to count
	// if numTrials=='all', then we use all trials
	var allData = jsPsych.data.getTrialsOfType('catlearncategorize');
	var allDataLength = allData.length;
	var startTrial;
	if (numTrials < allDataLength) {
		startTrial = allDataLength - numTrials;
		var totalNumTrials = numTrials;
	} else {
		startTrial = 0;
		var totalNumTrials = allDataLength;
	}
	var numCorrect = 0;
	var i;
	for (i=startTrial; i < allDataLength; i++) {
		if (allData[i]['correct']) {
			numCorrect ++;
		}
	}
	var rate = numCorrect / totalNumTrials;
	return({
		correct: numCorrect,
		total: totalNumTrials,
		rate: rate
	}
	);
};

var feedbackText = function () {
	var numRight = successes('all');
	percRate = Math.round(numRight.rate * 100);
	return(
		'<p style="font-size:120%;text-align:center;">So far, you have\
		 correctly identified ' + numRight.correct +
		' out of ' + numRight.total + ' plants, for a success rate of ' +
		percRate + '%.</p><p style="text-align:center;">Press the spacebar to \
		continue.</p>'
	);
};

var checkCriterion = function () {
	if (critMet) {
		jsPsych.endCurrentTimeline();
		return;
	};

	numRight = successes(20);
	if (numRight.total >= minTrials && numRight.rate >= criterion) {
		critMet = true;
		jsPsych.endCurrentTimeline();
	}
};

var lastTrial = function() {
	// TODO: fill in the instructions here.
	saveData(dataFileName, jsPsych.data.dataAsCSV);
	console.log('saveData() called');
	var numRight = successes(20);
	var ratePerc = Math.round(100 * numRight.rate);
	if (critMet) {
		var text = '<p style="text-align:center;color:green;">\
		Congratulations! You correctly identified ' + ratePerc +
		'% of the last 20 plants.</p>\
		<p style="text-align:center;">\
		The study is now over. Thank you for participating in this study.\
		</p>'
	} else {
		var text = '<p style="text-align:center;color:green;">\
		Great job! You correctly identified ' + ratePerc +
		'% of the last 20 plants.\
		</p><p style="text-align:center;">\
		The study is now over. Thank you for participating in this study.\
		</p>'
	}
	return(text);
};

// TODO: max number of trials, or meet learning criterion (20 at 95%, 200 trial max))

// Timeline initialization. These parameters are applied to every trial.
var block = {
	type: 'catlearncategorize',
	is_html: true,
	timeline: [],
	timing_post_trial: 0,
	on_finish: checkCriterion
};

for(i=0; i<numTestTrials; i++) {
	// set up the timeline.
	switch (cond) {
		case 'match':
		var fam1FeedLabs = ['./Family1Lab.jpeg', './Family1Lab.jpeg'];
		var fam2FeedLabs = ['./Family2Lab.jpeg', './Family2Lab.jpeg'];
		// Set up the timelines

		// Make 2 learning trials for each family, and a test trial.
		f1T1Fl = [fam1TrialFiles.pop(), fam1TrialFiles.pop()];
		fam1T1Stim = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f1T1Fl], imSize
		);
		fam1T1StimF = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f1T1Fl, fam1FeedLabs], feedSize
		);

		f1T2Fl = [fam1TrialFiles.pop(), fam1TrialFiles.pop()];
		fam1T2Stim = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f1T2Fl], imSize
		);
		fam1T2StimF = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f1T2Fl, fam1FeedLabs], feedSize
		);

		f2T1Fl = [fam2TrialFiles.pop(), fam2TrialFiles.pop()];
		fam2T1Stim = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f2T1Fl], imSize
		);
		fam2T1StimF = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f2T1Fl, fam2FeedLabs], feedSize
		);

		f2T2Fl = [fam2TrialFiles.pop(), fam2TrialFiles.pop()];
		fam2T2Stim = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f2T2Fl], imSize
		);
		fam2T2StimF = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f2T2Fl, fam2FeedLabs], feedSize
		);

		learnTrials = {
			choices: [fam1Key, fam2Key],
			show_stim_with_feedback: true,
			prompt: 'Press the <span style="font-size:120%">"e"</span> key if these \
			are ' + eKeyFamily +
			's and the <span style="font-size:120%">"i"</span> key if they are ' +
			iKeyFamily + 's.',
			//correct_text: 'Correct! These are from %ANS%.',
			//incorrect_text: 'Wrong! These are from %ANS%.',
			timing_feedback_duration: 1000,
			correct_text: "Correct!",
			incorrect_text: "Wrong!",
			timeline: [
				// Family 1 trials
				{
					stimulus: fam1T1Stim,
					feedback_stimulus: fam1T1StimF,
					key_answer: fam1KeyCode,
					text_answer: fam1Name,
					data: {
						file1: f1T1Fl[0],
						file2: f1T1Fl[1]
					}
				},
				{
					stimulus: fam1T2Stim,
					feedback_stimulus: fam1T2StimF,
					key_answer: fam1KeyCode,
					text_answer: fam1Name,
					data: {
						file1: f1T2Fl[0],
						file2: f1T2Fl[1]
					}
				},
				// Family 2 trials
				{
					stimulus: fam2T1Stim,
					feedback_stimulus: fam2T1StimF,
					key_answer: fam2KeyCode,
					text_answer: fam2Name,
					data: {
						file1: f2T1Fl[0],
						file2: f2T1Fl[1]
					}
				},
				{
					stimulus: fam2T2Stim,
					feedback_stimulus: fam2T2StimF,
					key_answer: fam2KeyCode,
					text_answer: fam2Name,
					data: {
						file1: f2T2Fl[0],
						file2: f2T2Fl[1]
					}
				}
			],
			randomize_order: true
		}
		break;
		case 'contrast':
		var fam1FeedLabs = ['./Family1Lab.jpeg', './Family2Lab.jpeg'];
		var fam2FeedLabs = ['./Family2Lab.jpeg', './Family1Lab.jpeg'];
		// Make 2 learning trials for each family, and a test trial.
		// Now, the family designation in the variable names, e.g., f1, refers to
		// the left-hand stimulus
		f1T1Fl = [fam1TrialFiles.pop(), fam2TrialFiles.pop()];
		fam1T1Stim = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f1T1Fl], imSize
		);
		fam1T1StimF = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f1T1Fl, fam1FeedLabs], feedSize
		);

		f1T2Fl = [fam1TrialFiles.pop(), fam2TrialFiles.pop()];
		fam1T2Stim = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f1T2Fl], imSize
		);
		fam1T2StimF = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f1T2Fl, fam1FeedLabs], feedSize
		);

		f2T1Fl = [fam2TrialFiles.pop(), fam1TrialFiles.pop()];
		fam2T1Stim = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f2T1Fl], imSize
		);
		fam2T1StimF = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f2T1Fl, fam2FeedLabs], feedSize
		);

		f2T2Fl = [fam2TrialFiles.pop(), fam1TrialFiles.pop()];
		fam2T2Stim = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f2T2Fl], imSize
		);
		fam2T2StimF = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
			[f2T2Fl, fam2FeedLabs], feedSize
		);

		learnTrials = {
			choices: [fam1Key, fam2Key],
			show_stim_with_feedback: true,
			prompt: '<p>Press the <span style="font-size:120%">"e"</span> key if the \
			fungus on the left is ' + eKeyFamily + ' and the fungus on the right is ' +
			iKeyFamily + '.</p><p>Press the <span style="font-size:120%">"i"</span>\
			key if the fungus on the left is ' + iKeyFamily + ' and the fungus on the\
			right is ' + eKeyFamily + '.</p>',
			//correct_text: 'Correct! These are from %ANS%.',
			//incorrect_text: 'Wrong! These are from %ANS%.',
			timing_feedback_duration: 1000,
			correct_text: "Correct!",
			incorrect_text: "Wrong!",
			timeline: [
				// Family 1 trials
				{
					stimulus: fam1T1Stim,
					feedback_stimulus: fam1T1StimF,
					key_answer: fam1KeyCode,
					text_answer: fam1Name,
					data: {
						file1: f1T1Fl[0],
						file2: f1T1Fl[1]
					}
				},
				{
					stimulus: fam1T2Stim,
					feedback_stimulus: fam1T2StimF,
					key_answer: fam1KeyCode,
					text_answer: fam1Name,
					data: {
						file1: f1T2Fl[0],
						file2: f1T2Fl[1]
					}
				},
				// Family 2 trials
				{
					stimulus: fam2T1Stim,
					feedback_stimulus: fam2T1StimF,
					key_answer: fam2KeyCode,
					text_answer: fam2Name,
					data: {
						file1: f2T1Fl[0],
						file2: f2T1Fl[1]
					}
				},
				{
					stimulus: fam2T2Stim,
					feedback_stimulus: fam2T2StimF,
					key_answer: fam2KeyCode,
					text_answer: fam2Name,
					data: {
						file1: f2T2Fl[0],
						file2: f2T2Fl[1]
					}
				}
			],
			randomize_order: true
		}
		break;
		default:
		throw "Not a valid condition in building stuff."
	}
	// Make the test trial.
	var curTrial = testTrialSet.pop();
	var testTrialStim = jsPsych.plugins['catlearn-vsl-grid-scene'].generate_stimulus(
		[ [curTrial[0]] ], imSize
	);
	var testTrial = {
		stimulus: testTrialStim,
		key_answer: curTrial[3],
		choices: [fam1Key, fam2Key],
		timing_feedback_duration: 0,
		timing_post_trial: 500,
		prompt: 'Is this a ' + eKeyFamily + ' (Press the "e" key), ' +
		'or a ' + iKeyFamily + ' (Press the "i" key)?',
		data: {
			family: curTrial[1],
			file1: curTrial[0]
		}
	};
	// Add them to the block timeline.
	block.timeline.push(learnTrials);
	block.timeline.push(testTrial);

	// every 20th trial add a feedback trial
	if ((i + 1) % 4 == 0 && i > 0) {
		// calculate success rate so far.
		numRight = successes('all');
		feedbackTrial = {
			type: 'text',
			text: feedbackText,
			cont_key: [32]
		};
		block.timeline.push(feedbackTrial);
	}
} // end for loop

// Add the condition, pid, other stuff, to the data
jsPsych.data.addProperties({
	pid: pid,
	condition: cond,
	e_family: eKeyFamily,
	i_family: iKeyFamily,
	date: pid
});

var timeline = [];
//TODO: Make an instruction block
var instText1 = '\
<p>\
The purpose of this study is to test your ability to learn to differentiate \
categories of stimuli.\
</p><p>\
Imagine that mankind has discovered a new planet, and you are one of the experts \
chosen to explore the planet for the first time. You discover two species of \
fungus on this planet: '+ fam1Name + 's and ' + fam2Name + 's. You know that \
they are different from genetic tests, but you cannot yet tell them apart just \
by looking at them. This computer program has identified several samples of \
the fungus, and it is going to help you learn to tell them apart.\
</p><p>\
The program will show you two enhanced images of the fungus at a time. You \
should do your best to learn which kind fungus is a ' + fam1Name + ', and which is a \
' + fam2Name + '. When you can complete 19 out of 20 identifications correctly, \
you will be finished learning the fungus. Good luck!\
</p><p style="text-align:center">\
Press the spacebar to continue.\
</p>\
';

if (cond=='match') {
	var instText2 = '<p>\
	The computer will show you two images of fungus. You should identify them by\
	pressing the "e" key if they are ' + eKeyFamily + 's, or by pressing the "i" \
	key if they are ' + iKeyFamily + 's. Every so often, the computer will test\
	you without feedback on a single fungus sample.</p><p style="text-align:center">\
	Press the spacebar to begin.\
	</p>\
	';
} else if (cond=='contrast') {
	var instText2	 = '<p>\
	The computer will show you two images of fungus. You should identify them by\
	pressing the "e" key if the one on the left is ' + eKeyFamily + ' and the , \
	one on the right is ' + iKeyFamily + ', or by pressing the "i" \
	key if the one on the left is ' + iKeyFamily + ' and the one on the right is \
	' + eKeyFamily + '. Every so often, the computer will test\
	you without feedback on a single fungus sample.</p><p style="text-align:center">\
	Press the spacebar to begin.\
	</p>\
	';
}
instr_block = {
	type: 'text',
	cont_key: [32],
	timing_post_trial: 30,
	timeline: [
		{text: instText1},
		{text: instText2}
	]
};
timeline.push(instr_block);
timeline.push(block);

var end_trial = {
	type: 'text',
	text: lastTrial
}
timeline.push(end_trial);

var dataFileName = String(pid) + '.csv'

jsPsych.init({
	timeline: timeline,
	on_finish: function() {
		//jsPsych.data.displayData();
		// TODO: Make the filename a composite of the ID and the date.
		;
	}
});

</script>
</html>
