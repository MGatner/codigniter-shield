<?php

namespace Sparks\Shield\Models;

use CodeIgniter\Model;
use Sparks\Shield\Entities\User;
use Sparks\Shield\Interfaces\UserProvider;
use Sparks\Shield\Authentication\Traits\UserProvider as UserProviderTrait;
use Faker\Generator;

class UserModel extends Model implements UserProvider
{
	use UserProviderTrait;

	protected $table      = 'users';
	protected $primaryKey = 'id';

	protected $returnType     = User::class;
	protected $useSoftDeletes = true;

	protected $allowedFields = [
		'username',
		'status',
		'status_message',
		'active',
		'deleted_at',
	];

	protected $useTimestamps = true;

	protected $afterFind = ['fetchIdentities'];

	/**
	 * Whether identity records should be included
	 * when user records are fetched from the database.
	 *
	 * @var boolean
	 */
	protected $fetchIdentities = false;

	/**
	 * Mark the next find* query to include identities
	 */
	public function withIdentities()
	{
		$this->fetchIdentities = true;

		return $this;
	}

	/**
	 * Populates identities for all records
	 * returned from a find* method. Called
	 * automatically when $this->fetchIdentities == true
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	protected function fetchIdentities(array $data)
	{
		if (! $this->fetchIdentities)
		{
			return $data;
		}

		$userIds = $data['singleton']
			? array_column($data, 'id')
			: array_column($data['data'], 'id');

		// Get our identities for all users
		$identities = model(UserIdentityModel::class)
			->whereIn('user_id', $userIds)
			->find();

		if (empty($identities))
		{
			return $data;
		}

		// Map our users by ID to make assigning simpler
		$mappedUsers = [];
		$users       = $data['singleton']
			? $data
			: $data['data'];
		foreach ($users as $user)
		{
			$mappedUsers[$user->id] = $user;
		}
		unset($users);

		// Now assign the identities to the user
		foreach ($identities as $id)
		{
			$array                                 = $mappedUsers[$id->user_id]->identities;
			$array[]                               = $id;
			$mappedUsers[$id->user_id]->identities = $array;
		}

		$data['data'] = $mappedUsers;

		return $data;
	}

	public function fake(Generator &$faker)
	{
		return [
			'username' => $faker->userName,
			'active'   => true,
		];
	}
}
