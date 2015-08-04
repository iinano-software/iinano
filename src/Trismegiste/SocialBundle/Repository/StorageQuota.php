<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Repository;

/**
 * StorageQuota is a monitoring for storage quota
 */
class StorageQuota
{

    /** @var \MongoCollection */
    protected $collection;
    protected $alias;

    public function __construct(\MongoCollection $coll, $pictureAlias)
    {
        $this->collection = $coll;
        $this->alias = $pictureAlias;
    }

    public function getPictureTotalSize()
    {
        $result = $this->collection->aggregate([
            ['$match' => ['-class' => $this->alias]],
            ['$project' => ['size' => true, '-class' => true]],
            ['$group' => ['_id' => '$-class', 'total' => ['$sum' => '$size']]]
        ]);

        $total = 0;

        if (($result['ok'] == 1) && (count($result['result']))) {
            $total = $result['result'][0]['total'];
        }

        return $total;
    }

    public function deleteExceedingQuota($quota, PictureRepository $repo)
    {
        $threshold = 0.9 * $quota;
        $currentSize = $this->getPictureTotalSize();

        if ($currentSize > $threshold) {
            $toPurge = $currentSize - $threshold;
            // cleaning
            $cursor = $this->collection->find(['-class' => 'picture'], ['storageKey' => true, 'size' => true])
                    ->sort(['_id' => 1]);  // starting from older pictures

            $sum = 0;
            foreach ($cursor as $item) {
                if ($sum < $toPurge) {
                    $repo->remove($item['storageKey']);
                    $this->collection->remove(['_id' => $item['_id']]);
                    $sum += $item['size'];
                } else {
                    // when we have deleted enough old pictures to reach the exceeding size to purge
                    break;
                }
            }
        }
    }

}
