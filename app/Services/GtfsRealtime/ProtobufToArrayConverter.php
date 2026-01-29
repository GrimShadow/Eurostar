<?php

namespace App\Services\GtfsRealtime;

use Transit_realtime\FeedMessage;
use Transit_realtime\TripDescriptor\ScheduleRelationship;

class ProtobufToArrayConverter
{
    /**
     * Convert raw GTFS-Realtime Protobuf binary to the canonical array shape
     * expected by FetchGtfsRealtime (same as JSON feed: ['entity' => [...]]).
     *
     * @return array{entity: array<int, array<string, mixed>>}
     */
    public function convert(string $binary): array
    {
        $feed = new FeedMessage;
        $feed->mergeFromString($binary);

        $entities = [];
        foreach ($feed->getEntity() as $feedEntity) {
            $tripUpdate = $feedEntity->getTripUpdate();
            if (! $tripUpdate) {
                continue;
            }

            $entity = [
                'id' => $feedEntity->getId(),
                'trip_update' => $this->tripUpdateToArray($tripUpdate),
            ];
            $entities[] = $entity;
        }

        return ['entity' => $entities];
    }

    /**
     * @return array{trip: array<string, mixed>, stop_time_update: array<int, array<string, mixed>>}
     */
    private function tripUpdateToArray(\Transit_realtime\TripUpdate $tripUpdate): array
    {
        $trip = $tripUpdate->getTrip();
        $tripArray = [
            'trip_id' => $trip ? $trip->getTripId() : '',
            'start_date' => $trip ? $trip->getStartDate() : '',
            'schedule_relationship' => $this->scheduleRelationshipName($trip),
        ];

        $stopTimeUpdates = [];
        foreach ($tripUpdate->getStopTimeUpdate() as $stu) {
            $stopTimeUpdates[] = $this->stopTimeUpdateToArray($stu);
        }

        return [
            'trip' => $tripArray,
            'stop_time_update' => $stopTimeUpdates,
        ];
    }

    /**
     * @return array{stop_sequence?: int, departure?: array{delay?: int}, arrival?: array{delay?: int}, stop_time_properties: array{assigned_stop_id?: string}}
     */
    private function stopTimeUpdateToArray(\Transit_realtime\TripUpdate\StopTimeUpdate $stu): array
    {
        $arr = [
            'stop_sequence' => $stu->getStopSequence(),
            'departure' => [],
            'arrival' => [],
            'stop_time_properties' => [],
        ];

        $dep = $stu->getDeparture();
        if ($dep !== null) {
            $arr['departure'] = ['delay' => $dep->getDelay()];
        }

        $arrival = $stu->getArrival();
        if ($arrival !== null) {
            $arr['arrival'] = ['delay' => $arrival->getDelay()];
        }

        return $arr;
    }

    private function scheduleRelationshipName(?\Transit_realtime\TripDescriptor $trip): string
    {
        if (! $trip) {
            return 'SCHEDULED';
        }

        try {
            return ScheduleRelationship::name($trip->getScheduleRelationship());
        } catch (\UnexpectedValueException) {
            return 'SCHEDULED';
        }
    }
}
