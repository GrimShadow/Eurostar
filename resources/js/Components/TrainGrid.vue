<template>
    <div class="train-grid">
        <div v-for="train in trains" :key="train.trip_id" class="train-row">
            <div class="train-info">
                <span class="train-id">{{ train.trip_id }}</span>
                <span class="train-route">{{ train.route_short_name }}</span>
                <div class="train-status">
                    <select v-model="train.status" @change="updateTrainStatus(train.trip_id, train.status)">
                        <option value="On time">On Time</option>
                        <option value="Delayed">Delayed</option>
                        <option value="Cancelled">Cancelled</option>
                        <option value="Completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="stops-container">
                <div v-for="stop in train.stops" :key="stop.stop_id" 
                     class="stop-item"
                     :class="getStopStatusClass(stop)">
                    <div class="stop-name">{{ stop.stop_name }}</div>
                    <div class="stop-times">
                        <span class="scheduled">{{ formatTime(stop.scheduled_arrival_time) }}</span>
                        <span v-if="stop.actual_arrival_time" class="actual">
                            {{ formatTime(stop.actual_arrival_time) }}
                        </span>
                    </div>
                    <div class="stop-status">
                        <select v-model="stop.status" @change="updateStopStatus(train.trip_id, stop)">
                            <option value="on-time">On Time</option>
                            <option value="delayed">Delayed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div v-if="stop.platform_code" class="platform">
                        Platform {{ stop.platform_code }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import axios from 'axios';

export default {
    setup() {
        const trains = ref([]);

        const loadTrains = async () => {
            try {
                const response = await axios.get('/api/gtfs/trains');
                trains.value = response.data.data;
                
                // Load train statuses
                for (const train of trains.value) {
                    const statusResponse = await axios.get(`/api/gtfs/train-status/${train.trip_id}`);
                    if (statusResponse.data.data) {
                        train.status = statusResponse.data.data.status;
                    } else {
                        train.status = 'On time';
                    }
                }
            } catch (error) {
                console.error('Error loading trains:', error);
            }
        };

        const updateTrainStatus = async (tripId, status) => {
            try {
                await axios.post('/api/gtfs/train-status', {
                    trip_id: tripId,
                    status: status
                });
            } catch (error) {
                console.error('Error updating train status:', error);
            }
        };

        const updateStopStatus = async (tripId, stop) => {
            try {
                await axios.post('/api/gtfs/stop-status', {
                    trip_id: tripId,
                    stop_id: stop.stop_id,
                    status: stop.status,
                    actual_arrival_time: stop.actual_arrival_time,
                    actual_departure_time: stop.actual_departure_time,
                    platform_code: stop.platform_code
                });
            } catch (error) {
                console.error('Error updating stop status:', error);
            }
        };

        const getStopStatusClass = (stop) => {
            return {
                'status-on-time': stop.status === 'on-time',
                'status-delayed': stop.status === 'delayed',
                'status-cancelled': stop.status === 'cancelled',
                'status-completed': stop.status === 'completed'
            };
        };

        const formatTime = (time) => {
            if (!time) return '';
            return new Date(`2000-01-01T${time}`).toLocaleTimeString([], { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
        };

        onMounted(() => {
            loadTrains();
        });

        return {
            trains,
            updateTrainStatus,
            updateStopStatus,
            getStopStatusClass,
            formatTime
        };
    }
};
</script>

<style scoped>
.train-grid {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.train-row {
    border: 1px solid #e2e8f0;
    border-radius: 0.5rem;
    padding: 1rem;
}

.train-info {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
    align-items: center;
}

.train-status select {
    padding: 0.25rem;
    border-radius: 0.25rem;
    border: 1px solid #e2e8f0;
    background-color: white;
}

.stops-container {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    padding: 0.5rem 0;
}

.stop-item {
    min-width: 200px;
    padding: 0.5rem;
    border-radius: 0.25rem;
    background-color: #f8fafc;
}

.stop-name {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.stop-times {
    display: flex;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.scheduled {
    color: #64748b;
}

.actual {
    color: #0f766e;
}

.stop-status select {
    width: 100%;
    margin-top: 0.5rem;
    padding: 0.25rem;
    border-radius: 0.25rem;
    border: 1px solid #e2e8f0;
}

.platform {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #64748b;
}

.status-on-time {
    border-left: 4px solid #22c55e;
}

.status-delayed {
    border-left: 4px solid #f59e0b;
}

.status-cancelled {
    border-left: 4px solid #ef4444;
}

.status-completed {
    border-left: 4px solid #3b82f6;
}
</style> 