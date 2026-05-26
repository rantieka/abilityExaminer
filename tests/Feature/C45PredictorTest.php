<?php

use App\Services\C45Predictor;
use App\Models\Setting;

// Ensure we close Mockery after each test
afterEach(function () {
    Mockery::close();
});

test('predicts REJECTED if AI score is less than or equal to threshold', function () {
	$settingMock = Mockery::mock('alias:' . Setting::class);
	$settingMock->shouldReceive('get')
		->with('c45_ai_threshold', 57.0)
		->andReturn(57.0);
	$settingMock->shouldReceive('get')
		->with('c45_test_threshold', 63.0)
		->andReturn(63.0);

	$decision = C45Predictor::predict(56.9, 100.0);
	expect($decision)->toBe('REJECTED');

	$decision = C45Predictor::predict(57.0, 100.0);
	expect($decision)->toBe('REJECTED');
});

test('predicts REJECTED if AI score is above threshold but Test score is less than or equal to threshold', function () {
	$settingMock = Mockery::mock('alias:' . Setting::class);
	$settingMock->shouldReceive('get')
		->with('c45_ai_threshold', 57.0)
		->andReturn(57.0);
	$settingMock->shouldReceive('get')
		->with('c45_test_threshold', 63.0)
		->andReturn(63.0);

	$decision = C45Predictor::predict(60.0, 62.9);
	expect($decision)->toBe('REJECTED');

	$decision = C45Predictor::predict(60.0, 63.0);
	expect($decision)->toBe('REJECTED');
});

test('predicts ACCEPTED if both scores are strictly above thresholds', function () {
	$settingMock = Mockery::mock('alias:' . Setting::class);
	$settingMock->shouldReceive('get')
		->with('c45_ai_threshold', 57.0)
		->andReturn(57.0);
	$settingMock->shouldReceive('get')
		->with('c45_test_threshold', 63.0)
		->andReturn(63.0);

	$decision = C45Predictor::predict(57.1, 63.1);
	expect($decision)->toBe('ACCEPTED');
});

test('respects custom settings for thresholds in prediction', function () {
	$settingMock = Mockery::mock('alias:' . Setting::class);
	$settingMock->shouldReceive('get')
		->with('c45_ai_threshold', 57.0)
		->andReturn(65.0);
	$settingMock->shouldReceive('get')
		->with('c45_test_threshold', 63.0)
		->andReturn(75.0);

	$decision = C45Predictor::predict(60.0, 80.0);
	expect($decision)->toBe('REJECTED');

	$decision = C45Predictor::predict(70.0, 70.0);
	expect($decision)->toBe('REJECTED');

	$decision = C45Predictor::predict(70.0, 80.0);
	expect($decision)->toBe('ACCEPTED');
});

test('returns correct confidence levels based on rules', function () {
	$settingMock = Mockery::mock('alias:' . Setting::class);
	$settingMock->shouldReceive('get')
		->with('c45_ai_threshold', 57.0)
		->andReturn(57.0);
	$settingMock->shouldReceive('get')
		->with('c45_test_threshold', 63.0)
		->andReturn(63.0);
	$settingMock->shouldReceive('get')
		->with('c45_leaf1_confidence', 88.2)
		->andReturn(88.2);
	$settingMock->shouldReceive('get')
		->with('c45_leaf2_confidence', 79.4)
		->andReturn(79.4);
	$settingMock->shouldReceive('get')
		->with('c45_leaf3_confidence', 90.6)
		->andReturn(90.6);

	// Case 1: AI score <= threshold
	expect(C45Predictor::getConfidence(50.0, 80.0))->toBe(88.2);

	// Case 2: AI score > threshold, Test score <= threshold
	expect(C45Predictor::getConfidence(60.0, 50.0))->toBe(79.4);

	// Case 3: AI score > threshold, Test score > threshold
	expect(C45Predictor::getConfidence(60.0, 70.0))->toBe(90.6);
});

test('respects custom confidence levels', function () {
	$settingMock = Mockery::mock('alias:' . Setting::class);
	$settingMock->shouldReceive('get')
		->with('c45_ai_threshold', 57.0)
		->andReturn(57.0);
	$settingMock->shouldReceive('get')
		->with('c45_test_threshold', 63.0)
		->andReturn(63.0);
	$settingMock->shouldReceive('get')
		->with('c45_leaf1_confidence', 88.2)
		->andReturn(95.5);
	$settingMock->shouldReceive('get')
		->with('c45_leaf2_confidence', 79.4)
		->andReturn(85.0);
	$settingMock->shouldReceive('get')
		->with('c45_leaf3_confidence', 90.6)
		->andReturn(99.1);

	// Case 1: AI score <= threshold
	expect(C45Predictor::getConfidence(50.0, 80.0))->toBe(95.5);

	// Case 2: AI score > threshold, Test score <= threshold
	expect(C45Predictor::getConfidence(60.0, 50.0))->toBe(85.0);

	// Case 3: AI score > threshold, Test score > threshold
	expect(C45Predictor::getConfidence(60.0, 70.0))->toBe(99.1);
});
