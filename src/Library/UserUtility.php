<?php
namespace Dniccum\CustomEmailSender\Library;


class UserUtility
{

    private $model;

    /**
     * UserUtility constructor.
     * @param Model|mixed $userClass
     */
    public function __construct($userClass)
    {
        $this->model = new $userClass;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function getAllUsers()
    {
        $selectQuery = $this->selectQuery();
        $userCollection = collect([]);

        $this->model->select($selectQuery)->chunk(200, function($users) use ($userCollection) {
            foreach ($users as $user) {
                $object = $this->buildResult($user);

                $userCollection->push($object);
            }
        });

        return $userCollection;
    }

    /**
     * @param string $query
     * @return array
     */
    public function searchUsers(string $query) : array
    {
        $selectQuery = $this->selectQuery();
        $queryPieces = explode(' ', $query);

        $userCollection = collect([]);

        foreach ($queryPieces as $piece) {
            $base = $this->model
                ->where(config('novaemailsender.model.email'), 'LIKE', '%'.$piece.'%')
                ->select($selectQuery);

            if (!empty(config('novaemailsender.model.name'))) {
                $query = $base->orWhere(config('novaemailsender.model.name'), 'LIKE', '%'.$piece.'%');
            } else {
                $query = $base->orWhere(config('novaemailsender.model.first_name'), 'LIKE', '%'.$piece.'%')
                    ->orWhere(config('novaemailsender.model.last_name'), 'LIKE', '%'.$piece.'%');
            }

            $query->chunk(200, function($users) use ($userCollection) {
                foreach ($users as $user) {
                    $object = $this->buildResult($user);

                    $userCollection->push($object);
                }
            });
        }

        return $userCollection
            ->unique(config('novaemailsender.model.email'))
            ->values()
            ->toArray();
    }

    /**
     * @return array|string[]
     */
    private function selectQuery()
    {
        $selectQuery = [ 'id', config('novaemailsender.model.email') ];

        if (!empty(config('novaemailsender.model.name'))) {
            $selectQuery[] = config('novaemailsender.model.name');
        } else {
            $selectQuery[] = config('novaemailsender.model.first_name');
            $selectQuery[] = config('novaemailsender.model.last_name');
        }

        return $selectQuery;
    }

    /**
     * @param $user
     * @return \stdClass
     */
    private function buildResult($user)
    {
        $email = config('novaemailsender.model.email');

        if (!empty(config('novaemailsender.model.name'))) {
            $nameProperty = config('novaemailsender.model.name');
            $name = $user->$nameProperty;
        } else {
            $firstNameProperty = config('novaemailsender.model.first_name');
            $lastNameProperty = config('novaemailsender.model.last_name');
            $name = $user->$firstNameProperty.' '.$user->$lastNameProperty;
        }

        $object = new \stdClass();
        $object->email = $user->$email;
        $object->name = $name;

        return $object;
    }

}