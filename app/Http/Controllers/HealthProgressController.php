<?php

namespace App\Http\Controllers;

use App\Models\HealthMetric;
use App\Models\HealthRecommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class HealthProgressController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $metrics = HealthMetric::forUser($user->id)
            ->recent(90)
            ->orderBy('recorded_at', 'desc')
            ->get()
            ->groupBy('metric_type');

        $recommendations = HealthRecommendation::where('user_id', $user->id)
            ->active()
            ->orderBy('priority', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $summary = $this->generateHealthSummary($metrics);

        return Inertia::render('Health/Progress', [
            'metrics' => $metrics,
            'recommendations' => $recommendations,
            'summary' => $summary
        ]);
    }

    public function storeMetric(Request $request)
    {
        $validated = $request->validate([
            'metric_type' => 'required|string',
            'value' => 'required|numeric',
            'unit' => 'nullable|string',
            'recorded_at' => 'required|date',
            'notes' => 'nullable|string'
        ]);

        $metric = HealthMetric::create([
            'user_id' => Auth::id(),
            ...$validated
        ]);

        $this->generateRecommendations(Auth::user());

        return response()->json(['success' => true, 'metric' => $metric]);
    }

    public function updateRecommendationStatus(Request $request, HealthRecommendation $recommendation)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,completed,dismissed'
        ]);

        $recommendation->update($validated);

        return response()->json(['success' => true]);
    }

    private function generateHealthSummary($metrics)
    {
        $summary = [];

        foreach ($metrics as $type => $typeMetrics) {
            $latest = $typeMetrics->first();
            $previous = $typeMetrics->skip(1)->first();
            
            $trend = 'stable';
            if ($previous) {
                if ($latest->value > $previous->value) {
                    $trend = 'increasing';
                } elseif ($latest->value < $previous->value) {
                    $trend = 'decreasing';
                }
            }

            $summary[$type] = [
                'current_value' => $latest->value,
                'unit' => $latest->unit,
                'trend' => $trend,
                'last_recorded' => $latest->recorded_at,
                'change' => $previous ? $latest->value - $previous->value : 0
            ];
        }

        return $summary;
    }

    private function generateRecommendations($user)
    {
        $metrics = HealthMetric::forUser($user->id)->recent(30)->get();
        
        foreach ($metrics->groupBy('metric_type') as $type => $typeMetrics) {
            $latest = $typeMetrics->first();
            
            if ($type === 'weight') {
                if ($latest->value > 90) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'weight'],
                        [
                            'title' => 'Significant Weight Loss Needed',
                            'description' => 'Your current weight puts you at risk for cardiovascular disease. Start with 30 minutes of walking daily and reduce portion sizes by 25%. Avoid processed foods and sugary drinks. Consider consulting a nutritionist for a personalized meal plan.',
                            'category' => 'nutrition',
                            'priority' => 'high',
                            'status' => 'active',
                            'target_value' => 75,
                            'generated_by' => 'system'
                        ]
                    );
                } elseif ($latest->value > 80) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'weight'],
                        [
                            'title' => 'Weight Management Required',
                            'description' => 'Maintain a balanced diet with lean proteins, whole grains, and plenty of vegetables. Exercise 3-4 times per week combining cardio and strength training. Track your daily calorie intake to stay within healthy limits.',
                            'category' => 'nutrition',
                            'priority' => 'medium',
                            'status' => 'active',
                            'target_value' => 75,
                            'generated_by' => 'system'
                        ]
                    );
                } elseif ($latest->value < 60) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'weight'],
                        [
                            'title' => 'Healthy Weight Gain Needed',
                            'description' => 'Your weight is below healthy range. Include protein-rich foods like nuts, eggs, and lean meats in every meal. Add healthy fats from avocados and olive oil. Consider speaking with a healthcare provider about safe weight gain strategies.',
                            'category' => 'nutrition',
                            'priority' => 'medium',
                            'status' => 'active',
                            'target_value' => 65,
                            'generated_by' => 'system'
                        ]
                    );
                }
            }

            if ($type === 'blood_pressure_systolic') {
                if ($latest->value > 160) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'blood_pressure_systolic'],
                        [
                            'title' => 'Urgent Blood Pressure Control',
                            'description' => 'Your blood pressure is dangerously high. Immediately reduce sodium intake to under 1500mg daily. Avoid alcohol and caffeine. Practice deep breathing exercises twice daily. Schedule an urgent appointment with your doctor for medication review.',
                            'category' => 'cardiovascular',
                            'priority' => 'high',
                            'status' => 'active',
                            'target_value' => 120,
                            'generated_by' => 'system'
                        ]
                    );
                } elseif ($latest->value > 140) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'blood_pressure_systolic'],
                        [
                            'title' => 'Blood Pressure Management',
                            'description' => 'Your blood pressure is elevated. Reduce salt intake and increase potassium-rich foods like bananas and spinach. Exercise regularly with 150 minutes of moderate activity weekly. Practice stress management through meditation or yoga.',
                            'category' => 'cardiovascular',
                            'priority' => 'medium',
                            'status' => 'active',
                            'target_value' => 120,
                            'generated_by' => 'system'
                        ]
                    );
                } elseif ($latest->value < 90) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'blood_pressure_systolic'],
                        [
                            'title' => 'Low Blood Pressure Monitoring',
                            'description' => 'Your blood pressure is low. Stay well hydrated by drinking 8-10 glasses of water daily. Increase salt intake moderately if approved by your doctor. Avoid sudden position changes and consider compression stockings.',
                            'category' => 'cardiovascular',
                            'priority' => 'low',
                            'status' => 'active',
                            'target_value' => 110,
                            'generated_by' => 'system'
                        ]
                    );
                }
            }

            if ($type === 'blood_sugar') {
                if ($latest->value > 180) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'blood_sugar'],
                        [
                            'title' => 'Critical Blood Sugar Management',
                            'description' => 'Your blood sugar is critically high. Eliminate all refined sugars and simple carbohydrates immediately. Eat small, frequent meals with complex carbs and protein. Monitor levels 4 times daily. Contact your healthcare provider urgently for medication adjustment.',
                            'category' => 'diabetes',
                            'priority' => 'high',
                            'status' => 'active',
                            'target_value' => 100,
                            'generated_by' => 'system'
                        ]
                    );
                } elseif ($latest->value > 140) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'blood_sugar'],
                        [
                            'title' => 'Blood Sugar Control Needed',
                            'description' => 'Your blood sugar is elevated. Focus on low glycemic index foods like oats, beans, and vegetables. Limit portions of rice, bread, and pasta. Take a 10-minute walk after each meal to help lower glucose levels naturally.',
                            'category' => 'diabetes',
                            'priority' => 'medium',
                            'status' => 'active',
                            'target_value' => 100,
                            'generated_by' => 'system'
                        ]
                    );
                } elseif ($latest->value < 70) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'blood_sugar'],
                        [
                            'title' => 'Low Blood Sugar Prevention',
                            'description' => 'Your blood sugar is low. Carry glucose tablets or a small snack at all times. Eat regular meals every 3-4 hours with complex carbohydrates. Avoid skipping meals and monitor levels more frequently if you take diabetes medication.',
                            'category' => 'diabetes',
                            'priority' => 'medium',
                            'status' => 'active',
                            'target_value' => 90,
                            'generated_by' => 'system'
                        ]
                    );
                }
            }

            if ($type === 'heart_rate') {
                if ($latest->value > 100) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'heart_rate'],
                        [
                            'title' => 'Elevated Heart Rate Management',
                            'description' => 'Your resting heart rate is high. Reduce caffeine intake and practice relaxation techniques. Gradually increase aerobic exercise to strengthen your heart. Ensure adequate sleep of 7-9 hours nightly. Monitor for any chest pain or shortness of breath.',
                            'category' => 'cardiovascular',
                            'priority' => 'medium',
                            'status' => 'active',
                            'target_value' => 70,
                            'generated_by' => 'system'
                        ]
                    );
                } elseif ($latest->value < 50) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'heart_rate'],
                        [
                            'title' => 'Low Heart Rate Monitoring',
                            'description' => 'Your heart rate is quite low. If you are not an athlete, this may indicate an underlying condition. Monitor for dizziness, fatigue, or fainting. Consult your doctor if you experience any symptoms or if this is a new change.',
                            'category' => 'cardiovascular',
                            'priority' => 'medium',
                            'status' => 'active',
                            'target_value' => 60,
                            'generated_by' => 'system'
                        ]
                    );
                }
            }

            if ($type === 'cholesterol') {
                if ($latest->value > 240) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'cholesterol'],
                        [
                            'title' => 'High Cholesterol Management',
                            'description' => 'Your cholesterol is dangerously high. Eliminate saturated fats from red meat, butter, and cheese. Include oats, beans, and fatty fish like salmon twice weekly. Add plant sterols and increase fiber intake. Consider discussing statin therapy with your doctor.',
                            'category' => 'cardiovascular',
                            'priority' => 'high',
                            'status' => 'active',
                            'target_value' => 200,
                            'generated_by' => 'system'
                        ]
                    );
                } elseif ($latest->value > 200) {
                    HealthRecommendation::updateOrCreate(
                        ['user_id' => $user->id, 'target_metric' => 'cholesterol'],
                        [
                            'title' => 'Cholesterol Reduction Plan',
                            'description' => 'Your cholesterol is borderline high. Replace butter with olive oil, choose lean proteins, and increase soluble fiber from fruits and vegetables. Exercise 30 minutes daily to boost HDL cholesterol. Limit processed foods and trans fats.',
                            'category' => 'cardiovascular',
                            'priority' => 'medium',
                            'status' => 'active',
                            'target_value' => 180,
                            'generated_by' => 'system'
                        ]
                    );
                }
            }
        }
    }
}
