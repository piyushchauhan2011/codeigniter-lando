<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\ContactMessageModel;
use Config\Services;

class Contact extends BaseController
{
    public function index(): string
    {
        return view('portal/contact', [
            'title'  => 'Contact',
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function submit()
    {
        if (! $this->validate('portal_contact')) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $ipKey = 'contact_rate_' . md5($this->request->getIPAddress());
        $count = (int) (cache()->get($ipKey) ?? 0);
        if ($count >= 10) {
            return redirect()->back()->withInput()->with('error', 'Too many messages. Try again later.');
        }
        cache()->save($ipKey, $count + 1, 3600);

        model(ContactMessageModel::class, false)->insert([
            'name'    => (string) $this->request->getPost('name'),
            'email'   => (string) $this->request->getPost('email'),
            'subject' => (string) $this->request->getPost('subject'),
            'body'    => (string) $this->request->getPost('body'),
        ]);

        log_message('info', 'Contact message stored from {email}', ['email' => $this->request->getPost('email')]);

        return redirect()->to(site_url(Services::portalLocale()->localizePath('contact')))->with('message', 'Thanks — your message was received.');
    }
}
