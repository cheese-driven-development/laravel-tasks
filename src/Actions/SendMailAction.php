<?php

namespace CheeseDriven\LaravelTasks\Actions;

use CheeseDriven\LaravelTasks\Contracts\Action;
use CheeseDriven\LaravelTasks\Contracts\Afterable;
use CheeseDriven\LaravelTasks\Contracts\Beforeable;
use CheeseDriven\LaravelTasks\Events\TaskMailSentEvent;
use CheeseDriven\LaravelTasks\Models\Task;
use CheeseDriven\LaravelTasks\Support\ClassResolver;
use Exception;
use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Mail;

class SendMailAction implements Action
{
    use ClassResolver;

    public function handle(Task $task)
    {
        try {
            $this->sendMail($task);
        } catch (Exception $exception) {
            $task->logFailure($exception->getMessage());

            report($exception);
        }
    }

    private function sendMail(Task $task)
    {
        $mailable = $task->getMailable();

        if ($mailable instanceof Mailable) {
            if ($mailable instanceof Beforeable) {
                $mailable->before();
            }

            Mail::to($task->getRecipients())->send($mailable);

            $task->logSuccess('Mail sent successfully');

            if ($mailable instanceof Afterable) {
                $mailable->after();
            }

            event(new TaskMailSentEvent($task));

            $task->complete();
        }
    }
}
