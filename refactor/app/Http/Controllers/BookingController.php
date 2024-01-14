<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use App\Http\Requests\BookingControllerIndexRequest;
use App\Http\Requests\BookingControllerStoreRequest;
use App\Http\Requests\BookingControllerUpdateRequest;
use App\Http\Requests\BookingControllerImmediateJobEmailRequest;
use App\Http\Requests\BookingControllerGetHistoryRequest;
use App\Http\Requests\BookingControllerAcceptJobRequest;
use App\Http\Requests\BookingControllerAcceptJobWithIdRequest;
use App\Http\Requests\BookingControllerCancelJobRequest;
use App\Http\Requests\BookingControllerEndJobRequest;
use App\Http\Requests\BookingControllerCustomerNotCallRequest;
use App\Http\Requests\BookingControllerDistanceFeedRequest;
use App\Http\Requests\BookingControllerReOpenRequest;
use App\Http\Requests\BookingControllerResendNotificationsRequest;
use App\Http\Requests\BookingControllerResendSmsNotificationsRequest;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(BookingControllerIndexRequest $request)
    {
        $request = $request->validated();
        
        if($request->get('user_id'))
        {
            $response = $this->repository->getUsersJobs($request->get('user_id'));
        }

        elseif(in_array($request->__authenticatedUser->user_type, [env('ADMIN_ROLE_ID'),env('SUPERADMIN_ROLE_ID')]))
        {
            $response = $this->repository->getAll($request);
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        return response($job);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(BookingControllerStoreRequest $request)
    {
        $response = $this->repository->store($request->__authenticatedUser, $request->validated());

        return response($response);
    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, BookingControllerUpdateRequest $request)
    {
        $response = $this->repository->updateJob($id, array_except($request->validated(), ['_token', 'submit']), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(BookingControllerImmediateJobEmailRequest $request)
    {
        $response = $this->repository->storeJobEmail($request->validated());

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(BookingControllerGetHistoryRequest $request)
    {
        $request = $request->validated();

        if($request->get('user_id')) {
            $response = $this->repository->getUsersJobsHistory($request->get('user_id'), $request);
            return response($response);
        }

        return null;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(BookingControllerAcceptJobRequest $request)
    {
        $response = $this->repository->acceptJob($request->validated(), $request->__authenticatedUser);

        return response($response);
    }

    public function acceptJobWithId(BookingControllerAcceptJobWithIdRequest $request)
    {
        $request = $request->validated();

        $response = $this->repository->acceptJobWithId($request->get('job_id'), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(BookingControllerCancelJobRequest $request)
    {
        $response = $this->repository->cancelJobAjax($request->validated(), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(BookingControllerEndJobRequest $request)
    {
        $response = $this->repository->endJob($request->validated());

        return response($response);

    }

    public function customerNotCall(BookingControllerCustomerNotCallRequest $request)
    {
        $response = $this->repository->customerNotCall($request->validated());

        return response($response);

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $response = $this->repository->getPotentialJobs($request->__authenticatedUser);

        return response($response);
    }

    public function distanceFeed(BookingControllerDistanceFeedRequest $request)
    {
        $data = $request->validated();

        $distance = $time = $jobid = $session = $admincomment = "";
        $flagged = $manually_handled = $by_admin = 'no';

        if (!empty($data['distance']))
            $distance = $data['distance'];

        if (!empty($data['time']))
            $time = $data['time'];

        if (!empty($data['jobid']))
            $jobid = $data['jobid'];

        if (!empty($data['session_time']))
            $session = $data['session_time'];

        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '')
                return "Please, add comment";
            $flagged = 'yes';
        }
        
        if ($data['manually_handled'] == 'true')
            $manually_handled = 'yes';

        if ($data['by_admin'] == 'true')
            $by_admin = 'yes';

        if (!empty($data['admincomment']))
            $admincomment = $data['admincomment'];

        if (!empty($jobid) && ($time || $distance)) {
            $affectedRows = Distance::where('job_id', '=', $jobid)
                ->update([
                    'distance' => $distance,
                    'time' => $time
                ]);
        }

        if (!empty($jobid) && ($admincomment || $session || $flagged || $manually_handled || $by_admin)) {
            $affectedRows1 = Job::where('id', '=', $jobid)
                ->update([
                    'admin_comments' => $admincomment,
                    'flagged' => $flagged,
                    'session_time' => $session,
                    'manually_handled' => $manually_handled,
                    'by_admin' => $by_admin
                ]);
        }

        return response(['success' => 'Record updated!']);
    }

    public function reopen(BookingControllerReOpenRequest $request)
    {
        $response = $this->repository->reopen($request->validated());
        return response($response);
    }

    public function resendNotifications(BookingControllerResendNotificationsRequest $request)
    {
        $data = $request->validated();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        try {
            $this->repository->sendNotificationTranslator($job, $job_data, '*');
            return response(['success' => 'Push sent']);
        } catch (\Exception $e) {
            return response(['failure' => $e->getMessage()]);
        }
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(BookingControllerResendSmsNotificationsRequest $request)
    {
        $data = $request->validated();
        $job = $this->repository->find($data['jobid']);
        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['failure' => $e->getMessage()]);
        }
    }

}
