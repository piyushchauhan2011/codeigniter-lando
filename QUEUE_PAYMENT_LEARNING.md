# Queue Payments Learning Guide

This guide uses the job portal's fake featured-job payment flow to practice RabbitMQ, CodeIgniter Queue, retries, dead letters, and idempotency.

## Start The Stack

Rebuild once after adding RabbitMQ:

```bash
lando rebuild -y
lando start
```

Install dependencies if this is a fresh checkout:

```bash
composer install
```

Run migrations and demo data:

```bash
lando php spark migrate --all
lando php spark db:seed JobPortalDemoSeeder
```

RabbitMQ runs as the Lando service named `rabbitmq`. Use `lando info` for the exact management UI URL; on this app it will be one of the RabbitMQ URLs, for example:

```text
http://rabbitmq.my-first-lamp-app.lndo.site:8000/
http://rabbitmq.my-first-lamp-app.lndo.site:8080/
```

Login with `guest` / `guest`.

## Run The Payment Worker

Start a dedicated worker for payment jobs:

```bash
lando queue-work-payments
```

For one-shot learning runs, you can stop when the queue is empty:

```bash
lando php spark queue:work payments --stop-when-empty
```

## Trigger A Payment

1. Sign in as `employer@example.test` with password `password123`.
2. Open the employer dashboard.
3. Choose a fake gateway scenario beside a job.
4. Click **Feature this job ($49.99)**.
5. Open the payment detail page and refresh while the worker runs.

The app first writes a `portal_payment_intents` row, then publishes `process-featured-job-payment` to the `payments` queue. The worker writes one `portal_payment_attempts` row per attempt.

## Scenarios

- `Success`: the first worker attempt marks the payment `paid` and updates the job as featured for 30 days.
- `Transient fail twice, then succeed`: attempts 1 and 2 throw `TransientPaymentException`, CodeIgniter Queue waits 30 seconds between retries, and attempt 3 succeeds.
- `Permanent fail to DLQ`: the worker records a `portal_payment_dead_letters` row and marks the intent `dead_lettered` without retrying.
- `Exhaust retries to failed jobs + DLQ`: every attempt throws a transient error. On the final attempt the app writes a domain dead letter, then rethrows so CodeIgniter Queue also stores the failed job.

## Inspect Failures

List CodeIgniter Queue failed jobs:

```bash
lando queue-failed
```

Retry failed jobs from the `payments` queue:

```bash
lando php spark queue:retry all -queue payments
```

Flush old failed jobs when you are done experimenting:

```bash
lando php spark queue:flush
```

The domain DLQ is separate from CodeIgniter's failed-job table. Check the payment detail page or inspect `portal_payment_dead_letters` to see the payment-specific reason and payload.

## Idempotency Exercise

The employer dashboard includes an idempotency key with each payment form. If the same POST is submitted twice, the unique `portal_payment_intents.idempotency_key` constraint causes the controller to reuse the existing payment intent instead of creating a duplicate charge.

To see this manually:

1. Click **Feature this job**.
2. On the browser's form resubmission prompt, submit the same request again.
3. Confirm both requests land on the same payment intent instead of creating two intents.

This mirrors real payment APIs: the HTTP request can be repeated safely because the server treats the idempotency key as the identity of the operation.

## Useful Config

RabbitMQ defaults live in `app/Config/Queue.php` and can be overridden in `.env`:

```dotenv
queue.rabbitmq.host = rabbitmq
queue.rabbitmq.port = 5672
queue.rabbitmq.user = guest
queue.rabbitmq.password = guest
queue.rabbitmq.vhost = /
```
