<?php

namespace App\Controllers;

use App\Models\PostModel;

class Tutorial extends BaseController
{
    public function hello(): string
    {
        return view('tutorial/hello', [
            'name' => 'Piyush',
            'topics' => ['Routes', 'Views', 'Database', 'CSS/JS'],
        ]);
    }

    public function posts(): string
    {
        $posts = model(PostModel::class)
            ->orderBy('id', 'DESC')
            ->findAll();

        return view('tutorial/posts/index', ['posts' => $posts]);
    }

    public function newPost(): string
    {
        return view('tutorial/posts/new', [
            'errors' => session()->getFlashdata('errors') ?? [],
            'old' => session()->getFlashdata('old') ?? [],
        ]);
    }

    public function createPost()
    {
        $validation = service('validation');
        $rules = [
            'title' => 'required|min_length[3]|max_length[120]',
            'body' => 'required|min_length[10]',
        ];

        if (! $validation->setRules($rules)->withRequest($this->request)->run()) {
            return redirect()->to('/posts/new')->withInput()->with('errors', $validation->getErrors())->with('old', $this->request->getPost());
        }

        model(PostModel::class)->insert([
            'title' => (string) $this->request->getPost('title'),
            'body' => (string) $this->request->getPost('body'),
        ]);

        return redirect()->to('/posts')->with('message', 'Post created successfully.');
    }
}
