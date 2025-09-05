<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

interface Props {
    metrics: Record<string, any[]>;
    recommendations: any[];
    summary: Record<string, any>;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Health Progress',
        href: '/health/progress',
    },
];

defineProps<Props>();

const newMetric = ref({
    metric_type: 'weight',
    value: '',
    unit: 'kg',
    recorded_at: new Date().toISOString().slice(0, 16),
    notes: '',
});

const metricUnits: Record<string, string> = {
    weight: 'kg',
    blood_pressure_systolic: 'mmHg',
    blood_pressure_diastolic: 'mmHg',
    blood_sugar: 'mg/dL',
    heart_rate: 'bpm',
    cholesterol: 'mg/dL',
    temperature: '°C',
    bmi: '',
    oxygen_saturation: '%'
};

const updateUnit = () => {
    newMetric.value.unit = metricUnits[newMetric.value.metric_type] || '';
};

const submitMetric = async () => {
    try {
        await axios.post('/api/health/metrics', newMetric.value);
        router.reload();
        newMetric.value = {
            metric_type: 'weight',
            value: '',
            unit: 'kg',
            recorded_at: new Date().toISOString().slice(0, 16),
            notes: '',
        };
    } catch (error) {
        console.error('Error saving metric:', error);
    }
};

const updateRecommendationStatus = async (id: number, status: string) => {
    try {
        await axios.patch(`/api/health/recommendations/${id}`, { status });
        router.reload();
    } catch (error) {
        console.error('Error updating recommendation:', error);
    }
};

const formatMetricType = (type: string) => {
    return type.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase());
};

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString();
};

const getTrendColor = (trend: string) => {
    switch (trend) {
        case 'increasing':
            return 'text-red-400';
        case 'decreasing':
            return 'text-green-400';
        default:
            return 'text-gray-400';
    }
};

const getTrendIcon = (trend: string) => {
    switch (trend) {
        case 'increasing':
            return '↗';
        case 'decreasing':
            return '↘';
        default:
            return '→';
    }
};

const getPriorityColor = (priority: string) => {
    switch (priority) {
        case 'high':
            return 'bg-red-600 text-red-100';
        case 'medium':
            return 'bg-yellow-600 text-yellow-100';
        default:
            return 'bg-green-600 text-green-100';
    }
};
</script>

<template>
    <Head title="Health Progress" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="min-h-screen bg-black py-14">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-white">Health Progress</h1>
                    <p class="mt-2 text-gray-400">Track your health metrics and view personalized recommendations</p>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <div class="mb-6 rounded-lg border border-gray-700 bg-gray-800 p-6 shadow-lg">
                            <h2 class="mb-4 text-xl font-semibold text-white">Health Summary</h2>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div v-for="(metric, type) in summary" :key="type" class="rounded-lg bg-gray-700 p-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-300">{{ formatMetricType(type) }}</p>
                                            <p class="text-2xl font-bold text-white">
                                                {{ metric.current_value }}
                                                <span class="text-sm text-gray-400">{{ metric.unit }}</span>
                                            </p>
                                        </div>
                                        <div :class="getTrendColor(metric.trend)" class="text-sm">
                                            {{ getTrendIcon(metric.trend) }}
                                        </div>
                                    </div>
                                    <p class="mt-2 text-xs text-gray-400">Last updated: {{ formatDate(metric.last_recorded) }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-700 bg-gray-800 p-6 shadow-lg">
                            <h2 class="mb-4 text-xl font-semibold text-white">Add New Metric</h2>
                            <form @submit.prevent="submitMetric" class="space-y-4">
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Metric Type</label>
                                        <select
                                            v-model="newMetric.metric_type"
                                            @change="updateUnit"
                                            class="mt-1 block w-full rounded-lg border-0 bg-gray-700 text-white shadow-sm ring-1 ring-inset ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-blue-500 py-2.5 px-3"
                                        >
                                            <option value="weight">Weight</option>
                                            <option value="blood_pressure_systolic">Blood Pressure (Systolic)</option>
                                            <option value="blood_pressure_diastolic">Blood Pressure (Diastolic)</option>
                                            <option value="blood_sugar">Blood Sugar</option>
                                            <option value="heart_rate">Heart Rate</option>
                                            <option value="cholesterol">Cholesterol</option>
                                            <option value="temperature">Temperature</option>
                                            <option value="bmi">BMI</option>
                                            <option value="oxygen_saturation">Oxygen Saturation</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Value</label>
                                        <input
                                            v-model="newMetric.value"
                                            type="number"
                                            step="0.1"
                                            placeholder="Enter value"
                                            class="mt-1 block w-full rounded-lg border-0 bg-gray-700 text-white shadow-sm ring-1 ring-inset ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-blue-500 py-2.5 px-3"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Unit</label>
                                        <input
                                            v-model="newMetric.unit"
                                            type="text"
                                            readonly
                                            class="mt-1 block w-full rounded-lg border-0 bg-gray-600 text-gray-300 shadow-sm ring-1 ring-inset ring-gray-500 py-2.5 px-3 cursor-not-allowed"
                                        />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-300 mb-2">Date</label>
                                        <input
                                            v-model="newMetric.recorded_at"
                                            type="datetime-local"
                                            class="mt-1 block w-full rounded-lg border-0 bg-gray-700 text-white shadow-sm ring-1 ring-inset ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-blue-500 py-2.5 px-3"
                                        />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-300 mb-2">Notes</label>
                                    <textarea
                                        v-model="newMetric.notes"
                                        rows="3"
                                        placeholder="Optional notes about this measurement"
                                        class="mt-1 block w-full rounded-lg border-0 bg-gray-700 text-white shadow-sm ring-1 ring-inset ring-gray-600 focus:ring-2 focus:ring-inset focus:ring-blue-500 py-2.5 px-3"
                                    ></textarea>
                                </div>
                                <button 
                                    type="submit" 
                                    class="w-full rounded-lg bg-blue-600 px-4 py-3 text-white font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-800 transition-colors"
                                >
                                    Add Metric
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="rounded-lg border border-gray-700 bg-gray-800 p-6 shadow-lg">
                            <h2 class="mb-4 text-xl font-semibold text-white">Recommendations</h2>
                            <div v-if="recommendations && recommendations.length > 0" class="space-y-4">
                                <div v-for="recommendation in recommendations" :key="recommendation.id" class="rounded-lg bg-gray-700 p-4">
                                    <div class="flex flex-col items-start justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <h3 class="font-medium text-white">{{ recommendation.title }}</h3>
                                                <span :class="getPriorityColor(recommendation.priority)" class="rounded px-2 py-1 text-xs">
                                                    {{ recommendation.priority }}
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-300">{{ recommendation.description }}</p>
                                            <p class="mt-2 text-xs text-gray-400">{{ recommendation.category }}</p>
                                        </div>
                                        <div class="flex gap-2">
                                            <button
                                                @click="updateRecommendationStatus(recommendation.id, 'completed')"
                                                class="text-sm text-green-400 hover:text-green-300"
                                            >
                                                Complete
                                            </button>
                                            <button
                                                @click="updateRecommendationStatus(recommendation.id, 'dismissed')"
                                                class="text-sm text-red-400 hover:text-red-300"
                                            >
                                                Dismiss
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div v-else class="py-8 text-center">
                                <p class="text-gray-400">No recommendations available</p>
                                <p class="mt-2 text-sm text-gray-500">Add some health metrics to receive personalized recommendations</p>
                            </div>
                        </div>

                        <div class="rounded-lg border border-gray-700 bg-gray-800 p-6 shadow-lg">
                            <h2 class="mb-4 text-xl font-semibold text-white">Recent Metrics</h2>
                            <div class="space-y-3">
                                <div v-for="(typeMetrics, type) in metrics" :key="type">
                                    <h3 class="mb-2 text-sm font-medium text-gray-300">{{ formatMetricType(type) }}</h3>
                                    <div class="space-y-2">
                                        <div
                                            v-for="metric in typeMetrics.slice(0, 3)"
                                            :key="metric.id"
                                            class="flex items-center justify-between text-sm"
                                        >
                                            <span class="text-gray-400">{{ formatDate(metric.recorded_at) }}</span>
                                            <span class="text-white">{{ metric.value }} {{ metric.unit }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
