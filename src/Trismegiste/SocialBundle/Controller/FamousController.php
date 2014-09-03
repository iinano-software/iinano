<?php

/*
 * Iinano
 */

namespace Trismegiste\SocialBundle\Controller;

use Trismegiste\SocialBundle\Controller\Template;

/**
 * FamousController is a controller to manage action on entities with Famous contract
 */
class FamousController extends Template
{

    public function addFanOnCommentaryAction($id, $uuid)
    {
        $pub = $this->getRepository()->findByPk($id);
        $commentary = $pub->getCommentaryByUuid($uuid);
        $commentary->addFan($this->getAuthor());
        $this->getRepository()->persist($pub);

        return $this->redirectRouteOk('content_index', [], 'anchor-' . $id);
    }

    public function removeFanOnCommentaryAction($id, $uuid)
    {
        $pub = $this->getRepository()->findByPk($id);
        $commentary = $pub->getCommentaryByUuid($uuid);
        $commentary->removeFan($this->getAuthor());
        $this->getRepository()->persist($pub);

        return $this->redirectRouteOk('content_index', [], 'anchor-' . $id);
    }

    public function likePublishAction($id, $action)
    {
        $doc = $this->getRepository()->findByPk($id);
        switch ($action) {
            case'add':$doc->addFan($this->getAuthor());
                break;
            case'remove':$doc->removeFan($this->getAuthor());
                break;
            default: $this->createNotFoundException("$action");
        }
        $this->getRepository()->persist($doc);

        return $this->redirectRouteOk('content_index', [], 'anchor-' . $id);
    }

}
