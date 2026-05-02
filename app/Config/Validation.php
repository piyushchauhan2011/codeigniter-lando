<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Validation\StrictRules\CreditCardRules;
use CodeIgniter\Validation\StrictRules\FileRules;
use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;

use App\Validation\PortalUserRules;
use CodeIgniter\Shield\Authentication\Passwords\ValidationRules as ShieldPasswordRules;

class Validation extends BaseConfig
{
    // --------------------------------------------------------------------
    // Setup
    // --------------------------------------------------------------------

    /**
     * Stores the classes that contain the
     * rules that are available.
     *
     * Populated in {@see __construct()} so `PortalUserRules::class` is not in a
     * property default (avoids "Constant expression contains invalid operations").
     *
     * @var list<class-string>
     */
    public array $ruleSets = [];

    /**
     * Specifies the views that are used to display the
     * errors.
     *
     * @var array<string, string>
     */
    public array $templates = [
        'list'   => 'CodeIgniter\Validation\Views\list',
        'single' => 'CodeIgniter\Validation\Views\single',
    ];

    public function __construct()
    {
        $this->ruleSets = [
            PortalUserRules::class,
            ShieldPasswordRules::class,
            Rules::class,
            FormatRules::class,
            FileRules::class,
            CreditCardRules::class,
        ];

        parent::__construct();
    }

    // --------------------------------------------------------------------
    // Rules
    // --------------------------------------------------------------------

    /** @var array<string, string> */
    public array $portal_login = [
        'email'    => 'required|valid_email',
        'password' => 'required',
    ];

    /** @var array<string, string> */
    public array $portal_register = [
        'email'            => 'required|valid_email|is_unique[auth_identities.secret]',
        'password'         => 'required|strong_password[]',
        'password_confirm' => 'required|matches[password]',
        'role'             => 'required|in_list[employer,seeker]',
        'company_name'     => 'company_name_for_registration[_]',
    ];

    /** @var array<string, string> */
    public array $portal_job_form = [
        'title'            => 'required|min_length[3]|max_length[180]',
        'description'      => 'required|min_length[20]',
        'employment_type'  => 'required|in_list[full_time,part_time,contract]',
        'location'         => 'required|max_length[160]',
        'salary_min'       => 'permit_empty|integer|greater_than_equal_to[0]',
        'salary_max'       => 'permit_empty|integer|greater_than_equal_to[0]',
        'category_id'      => 'permit_empty|integer',
        'status'           => 'required|in_list[draft,published,closed]',
    ];

    /** @var array<string, string> */
    public array $portal_employer_profile = [
        'company_name' => 'required|min_length[2]|max_length[160]',
        'website'      => 'permit_empty|max_length[255]',
        'description'  => 'permit_empty|max_length[4000]',
    ];

    /** @var array<string, string> */
    public array $portal_seeker_profile = [
        'headline' => 'permit_empty|max_length[160]',
        'bio'      => 'permit_empty|max_length[4000]',
        'skills'   => 'permit_empty|max_length[2000]',
    ];

    /** @var array<string, string> */
    public array $portal_contact = [
        'name'    => 'required|max_length[120]',
        'email'   => 'required|valid_email',
        'subject' => 'required|max_length[180]',
        'body'    => 'required|min_length[10]|max_length[8000]',
    ];

    /** @var array<string, string> */
    public array $portal_apply = [
        'cover_letter' => 'required|min_length[20]|max_length[8000]',
    ];

    /** @var array<string, string> */
    public array $portal_job_asset_upload = [
        'asset' => 'uploaded[asset]|max_size[asset,5120]|ext_in[asset,png,jpg,jpeg,webp,pdf,doc,docx,txt]',
    ];
}
