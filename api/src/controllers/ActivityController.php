<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\ActivityModel;
use App\Core\Database;

/**
 * Controller for managing user activity tracking and retrieval
 */
class ActivityController extends Controller {
    /**
     * @var ActivityModel Activity model instance
     */
    private ActivityModel $activityModel;

    /**
     * Constructor for the ActivityController
     *
     * @param Request $request The HTTP request object
     */
    public function __construct(Request $request) {
        parent::__construct($request);
        $this->activityModel = new ActivityModel(Database::getInstance());
    }

    /**
     * Get user's recent activities
     * 
     * Endpoint: GET /api/activity
     * Optional query params:
     *   - limit: Number of activities to return (default: 10)
     *
     * @return void Sends JSON response
     */
    public function getActivity(): void {
        try {
            $user = $this->request->getUser();
            if (!$user) {
                $this->respondError('Unauthorized', 401);
                return;
            }

            $limit = $this->request->get('limit') ?? 10;
            
            // Get recent activities
            $activities = $this->activityModel->getRecentActivities($user['id'], $limit);

            $this->respond([
                'status' => 'success',
                'data' => array_map(function($activity) {
                    return [
                        'type' => $activity['type'] ?? 'unknown',
                        'title' => $activity['title'] ?? '',
                        'timestamp' => $activity['created_at'] ?? date('Y-m-d H:i:s'),
                        'quiz_title' => $activity['quiz_title'] ?? null,
                        'score' => isset($activity['score']) ? round($activity['score'], 1) : null
                    ];
                }, $activities)
            ]);
        } catch (\Exception $e) {
            $this->respond([
                'status' => 'success',
                'data' => []
            ]);
        }
    }

    /**
     * Record a new user activity
     * 
     * Endpoint: POST /api/activity
     * Required fields:
     *   - type: Activity type
     *   - title: Activity title
     * Optional fields:
     *   - quiz_id: Related quiz ID
     *   - quiz_attempt_id: Related quiz attempt ID
     *
     * @return void Sends JSON response
     */
    public function recordActivity(): void {
        try {
            $userId = $this->request->get('user_id');
            $data = $this->request->getData();

            if (!isset($data['type']) || !isset($data['title'])) {
                $this->respondError('Missing required fields', 400);
                return;
            }

            $activityId = $this->activityModel->create([
                'user_id' => $userId,
                'type' => $data['type'],
                'title' => $data['title'],
                'quiz_id' => $data['quiz_id'] ?? null,
                'quiz_attempt_id' => $data['quiz_attempt_id'] ?? null
            ]);

            $this->respond([
                'status' => 'success',
                'data' => [
                    'id' => $activityId
                ]
            ]);
        } catch (\Exception $e) {
            $this->respondError('Failed to record activity', 500);
        }
    }
}