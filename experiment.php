<?php
require_once('./Resources/pInfo.php');
?>

<!doctype html>
<html>
<head>
	<title>My experiment</title>
  <meta charset="utf-8" />
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/seedrandom/2.4.2/seedrandom.min.js"></script>
	<script src="jspsych-5.0.3/jspsych.js"></script>
	<script src="jspsych-5.0.3/plugins/jspsych-text.js"></script>
	<script src="jspsych-5.0.3/plugins/jspsych-single-stim.js"></script>
	<script src="jspsych-5.0.3/plugins/jspsych-catlearn-vsl-grid-scene.js"></script>
	<script src="jspsych-5.0.3/plugins/jspsych-catlearn-categorize.js"></script>
	<script src="https://cdn.rawgit.com/Cmell/JavascriptUtilsV9-20-2017/master/Util.js"></script>
	<link href="jspsych-5.0.3/css/jspsych.css" rel="stylesheet" type="text/css"></link>
</head>
<body>
</body>
<script>

// Query string parameters
var params = new URLSearchParams(window.location.search);

// Generate a seed for the random number generator
var d = new Date();
var seed = d.getTime();

// get the pid:
<?php
// Get the pid:
$pid = getNewPID("./Resources/PID.csv");
echo "pid = ".$pid.";";
?>

// Some utility variables
var pidStr = "00" + pid; pidStr = pidStr.substr(pidStr.length - 3);// lead 0s

var flPrefix = "./data/cat_"

var filename = flPrefix + pidStr + "_" + seed + ".csv";

var fields = [
  "pid",
  "condition",
  "seed",
	"prolific_pid",
  "e_family",
  "i_family",
	"y_family",
	"b_family",
  "trial_type",
  "file1",
  "file2",
  "trial_index",
	"test_family",
	"left_family",
	"right_family",
  "key_press",
  "correct",
  "rt",
  "time_elapsed",
  "trial_purpose"
];

Math.seedrandom(seed);

// Some variable declarations
var feedbackTrial, f1T1Fl, f1T2Fl, f2T1Fl, f2T2Fl, fam1T1Stim, fam1T2Stim;
var fam2T1Stim, fam2T2Stim, learnTrials, numRight;

// Get the condition
var condition = 'random';
var cond = condition == 'random' ? rndSelect(['match', 'contrast'], 1)[0] : condition;
// Some experiment parameters.
var numTestTrials = 30; // Should be even; also represents the maximum trials
var numLearnTrials = 4 * numTestTrials;
var minTrials = 20;
var criterion = .95;
var critMet	= false;

// Some image information for stimuli when building the blocks.
var imSize = [
	[[200, 200], [200, 200]]
];
var feedSize = [
	[[200, 200], [200, 200]],
	[[200, 40], [200, 40]]
];

// Some filenames to use later
fam1LabFl = './ThaiLab.png';
fam2LabFl = './ChineseLab.png';

var fam1Key =  rndSelect(['e', 'i'], 1)[0];
var fam1KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(fam1Key);
var fam2Key = fam1Key == 'e' ? 'i' : 'e';
var fam2KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(fam2Key);
var fam1Name = 'Thai';
var fam2Name = 'Chinese';
if (fam1Key == 'e') {
	var eKeyFamily = 'Thai';
	var iKeyFamily = 'Chinese';
} else if (fam1Key == 'i') {
	var eKeyFamily = 'Chinese';
	var iKeyFamily = 'Thai';
}

// Test keys should be different
var testKeys = ['b', 'y'];
var testFam1Key = rndSelect(testKeys, 1)[0];
var testFam2Key = testFam1Key == testKeys[0] ? testKeys[1] : testKeys[0];
var testFam1KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(testFam1Key);
var testFam2KeyCode = jsPsych.pluginAPI.convertKeyCharacterToKeyCode(testFam2Key);
if (testFam1Key == 'b') {
  var bTestKeyFamily = 'Thai';
  var yTestKeyFamily = 'Chinese';
} else if (testFam1Key == 'y') {
  var bTestKeyFamily = 'Chinese';
  var yTestKeyFamily = 'Thai';
}


// Get the file names of the families
var fam1Files = <?php echo json_encode(glob('./Family1/*.png')); ?>

var fam2Files = <?php echo json_encode(glob('./Family2/*.png')); ?>

// Preload the images
var allIms = fam1Files.concat(fam2Files);
var imsToLoad = new Array();
for (var i=0; i < allIms.length; i++) {
  var tempArr = [allIms[i], [imSize[0]]]
}
preloadResizedImages(allIms);

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
		testTrialSet.push([curFile, 'Thai', testFam1Key, testFam1KeyCode]);
	} else {
		testTrialSet.push([curFile, 'Chinese', testFam2Key, testFam2KeyCode]);
	}
};

var endTrial = function (trial) {
  var csvLn = trialObjToCSV(trial);
  saveData(filename, csvLn);
};

var generateHeader = function () {
  var line = '';
  var f;
  var fL = fields.length;
  for (i=0; i < fL; i++) {
    f = fields[i];
    if (i < fL - 1) {
      line += f + ',';
    } else {
      // don't include the comma on the last one.
      line += f;
    }
  }

  // Add an eol character or two
  line += '\r\n';
  return(line);
};

var sendHeader = function () {
  saveData(filename, generateHeader());
}

var trialObjToCSV = function (t) {
  // t is the trial object
  var f;
  var line = '';
  var fL = fields.length;
  var thing;

  for (i=0; i < fL; i++) {
    f = fields[i];
    thing = typeof t[f] === 'undefined' ? 'NA' : t[f];
    if (i < fL - 1) {
      line += thing + ',';
    } else {
      // Don't include the comma on the last one.
      line += thing;
    }
  }
  // Add an eol character or two
  line += '\r\n';
  return(line);
};

// Save data function
var saveData = function (filename, filedata) {
	$.ajax({
		type: 'post',
		cache: false,
		url: './Resources/SaveData.php',
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
		'<p style="font-size:120%;text-align:center;">So far, you were\
		 correct for ' + numRight.correct +
		' out of ' + numRight.total + ' trials, for a success rate of ' +
		percRate + '%.</p><p style="text-align:center;">Press the spacebar to \
		continue.</p>'
	);
};

// TODO: set up end criteria
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
		Congratulations! You were correct for ' + ratePerc +
		'% of the last 20 trials.</p>\
		<p style="text-align:center;">\
		You have completed the task. Please press the spacebar to continue to the survey.\
		</p>'
	} else {
		var text = '<p style="text-align:center;color:green;">\
		Great job! You were correct for ' + ratePerc +
		'% of the last 20 trials.\
		</p><p style="text-align:center;">\
		You have completed the task. Please press the spacebar to continue to the survey.\
		</p>'
	}
	return(text);
};

// ODO: max number of trials, or meet learning criterion (20 at 95%, 200 trial max))

// Initialize the data file
sendHeader();

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
		var fam1FeedLabs = [fam1LabFl, fam1LabFl];
		var fam2FeedLabs = [fam2LabFl, fam2LabFl];
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
			prompt: '<p>Type <span style="font-size:120%">"e"</span> if both \
			people are ' + eKeyFamily + '.</p><p>Type <span style="font-size:120%">"i"</span>\
			if both people are ' + iKeyFamily + '.</p>',
			//correct_text: 'Correct! These are from %ANS%.',
			//incorrect_text: 'Wrong! These are from %ANS%.',
			timing_feedback_duration: 1200,
			correct_text: "Correct!",
			incorrect_text: "Wrong!",
      data: {
        trial_purpose: 'learn',
      },
      on_finish: endTrial,
			timeline: [
				// Family 1 trials
				{
					stimulus: fam1T1Stim,
					feedback_stimulus: fam1T1StimF,
					key_answer: fam1KeyCode,
					text_answer: fam1Name,
					data: {
            // file1 refers to the left image, and file2 refers to the right image
						file1: f1T1Fl[0],
						file2: f1T1Fl[1],
						left_family: "Thai",
						right_family: "Thai"
					}
				},
				{
					stimulus: fam1T2Stim,
					feedback_stimulus: fam1T2StimF,
					key_answer: fam1KeyCode,
					text_answer: fam1Name,
					data: {
						file1: f1T2Fl[0],
						file2: f1T2Fl[1],
						left_family: "Thai",
						right_family: "Thai"
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
						file2: f2T1Fl[1],
						left_family: "Chinese",
						right_family: "Chinese"
					}
				},
				{
					stimulus: fam2T2Stim,
					feedback_stimulus: fam2T2StimF,
					key_answer: fam2KeyCode,
					text_answer: fam2Name,
					data: {
						file1: f2T2Fl[0],
						file2: f2T2Fl[1],
						left_family: "Chinese",
						right_family: "Chinese"
					}
				}
			],
			randomize_order: true
		}
		break;
		case 'contrast':
		var fam1FeedLabs = [fam1LabFl, fam2LabFl];
		var fam2FeedLabs = [fam2LabFl, fam1LabFl];
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
			prompt: '<p>Type <span style="font-size:120%">"e"</span> if the \
			person on the left is ' + eKeyFamily + ' and the person on the right is ' +
			iKeyFamily + '.</p><p>Type <span style="font-size:120%">"i"</span>\
			if the person on the left is ' + iKeyFamily + ' and the person on the\
			right is ' + eKeyFamily + '.</p>',
			//correct_text: 'Correct! These are from %ANS%.',
			//incorrect_text: 'Wrong! These are from %ANS%.',
			timing_feedback_duration: 1200,
			correct_text: "Correct!",
			incorrect_text: "Wrong!",
      data: {
        trial_purpose: 'learn'
      },
      on_finish: endTrial,
			timeline: [
				// Family 1 trials
				{
					stimulus: fam1T1Stim,
					feedback_stimulus: fam1T1StimF,
					key_answer: fam1KeyCode,
					text_answer: fam1Name,
					data: {
						file1: f1T1Fl[0],
						file2: f1T1Fl[1],
						left_family: "Thai",
						right_family: "Chinese"
					}
				},
				{
					stimulus: fam1T2Stim,
					feedback_stimulus: fam1T2StimF,
					key_answer: fam1KeyCode,
					text_answer: fam1Name,
					data: {
						file1: f1T2Fl[0],
						file2: f1T2Fl[1],
						left_family: "Thai",
						right_family: "Chinese"
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
						file2: f2T1Fl[1],
						family: "LeftChinese_RightThai",
						left_family: "Chinese",
						right_family: "Thai"
					}
				},
				{
					stimulus: fam2T2Stim,
					feedback_stimulus: fam2T2StimF,
					key_answer: fam2KeyCode,
					text_answer: fam2Name,
					data: {
						file1: f2T2Fl[0],
						file2: f2T2Fl[1],
						left_family: "Chinese",
						right_family: "Thai"
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
		choices: [testFam1Key, testFam2Key],
		timing_feedback_duration: 0,
		timing_post_trial: 500,
		prompt: 'Is this a ' + yTestKeyFamily + ' person (Press the "y" key), ' +
		'or a ' + bTestKeyFamily + ' person (Press the "b" key)?',
    on_finish: endTrial,
		data: {
			test_family: curTrial[1],
			file1: curTrial[0],
      trial_purpose: 'test'
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
	y_family: yTestKeyFamily,
	b_family: bTestKeyFamily,
	seed: seed,
	prolific_pid: params.get("PROLIFIC_PID")==null ? 'NA' : params.get("PROLIFIC_PID")
});

var timeline = [];

var instText1 = '\
<p>\
The purpose of this study is to test your ability to learn to differentiate \
categories of people.\
</p><p>\
In everyday life, most people are able to "categorize" other people quickly. \
Some categories are very obvious, such as gender: typically, it is easy to say \
if a person is a male or a female. Some categories are harder. For instance, \
most people from the U.S. might have trouble telling Mexican people apart from \
Argentinians. However, someone who has lived in Mexico or Argentina might have an \
easier time.\
</p><p>\
In this task, we want to see how quickly you can learn to differentiate two \
categories of people. We think it is likely that you have not had much \
experience with these two categories: ' + fam1Name + ' people, and ' + fam2Name
+ ' people. During this task, do your best to try and categorize each person. \
Note that you are NOT trying to remember each face! Rather, you are trying to \
say whether a person is ' + fam1Name + ' or ' + fam2Name + '.\
</p><p>\
The program will show you two faces at a time. You \
should do your best to learn which faces are ' + fam1Name + ', and which are \
' + fam2Name + '. When you can complete 19 out of 20 identifications correctly, \
you will be finished learning the nationalities. Good luck!\
</p><p style="text-align:center">\
Press the spacebar to continue.\
</p>\
';

if (cond=='match') {
	var instText2 = '<p>\
	The computer will show you two images of faces. You should identify them by\
	pressing the "e" key if they are ' + eKeyFamily + ', or by pressing the "i" \
	key if they are ' + iKeyFamily + '. Every so often, the computer will test\
	you without feedback on a single face.</p>\
	<p>\
	You might have to guess! Especially for the first couple of face-pairs, the \
	best strategy may be to guess which faces you are looking at, and pay \
	attention to the feedback to learn the real answer.\
	</p>\
	<p style="text-align:center">\
	Press the spacebar to begin.\
	</p>\
	';
} else if (cond=='contrast') {
	var instText2	 = '<p>\
	The computer will show you two images of faces. You should identify them by\
	pressing the "e" key if the one on the left is ' + eKeyFamily + ' and the , \
	one on the right is ' + iKeyFamily + ', or by pressing the "i" \
	key if the one on the left is ' + iKeyFamily + ' and the one on the right is \
	' + eKeyFamily + '. Every so often, the computer will test\
	you without feedback on a single face.</p>\
	<p>\
	You might have to guess! Especially for the first couple of face-pairs, the \
	best strategy may be to guess which faces you are looking at, and pay \
	attention to the feedback to learn the real answer.\
	</p>\
	<p style="text-align:center">\
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

var last_trial = {
	type: 'text',
	text: lastTrial
}
timeline.push(last_trial);

var dataFileName = 'EndTaskData/' + String(pid) + '_EndTaskData.csv'

var experimentEnd = function () {

	//Add the task PID.
	params.set("pid", pid);
	var queryString = params.toString();
	var url = "https://cuboulder.qualtrics.com/jfe/form/SV_8cXF1dwNVKszVzw?" +
		queryString;
	window.location = url;
};

jsPsych.init({
	timeline: timeline,
	on_finish: experimentEnd
});

</script>
</html>
