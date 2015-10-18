'use strict';

var gulp = require('gulp');
var concat = require('gulp-concat');

var quizJsFiles = [
	'./admin/js/quiz/models/question.js',
	'./admin/js/quiz/models/multiple-choice-answer.js',
	'./admin/js/quiz/collections/questions.js',
	'./admin/js/quiz/collections/multiple-choice-answers.js',
	'./admin/js/quiz/views/multiple-choice-answer.js',
	'./admin/js/quiz/views/question.js',
	'./admin/js/quiz/views/multiple-choice-question.js',
	'./admin/js/quiz/views/written-answer-question.js',
	'./admin/js/quiz/views/quiz.js',
	'./admin/js/quiz/quiz.js'
];

gulp.task('quizjs', function() {
	var stream = gulp.src(quizJsFiles).pipe(concat('quiz.min.js'));

	stream.pipe(gulp.dest('./admin/js/quiz'));
});

gulp.task('watch', function() {
	gulp.watch(quizJsFiles, ['quizjs']);
});

gulp.task('default', ['quizjs']);
