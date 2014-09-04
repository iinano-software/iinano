<?php

/*
 * Iinano
 */

namespace Trismegiste\SocialBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Trismegiste\Socialist\Content;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Trismegiste\SocialBundle\Utils\SkippableIterator;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * ContentController is a template for the wall/dashboard
 *
 * The user NEEDS to be authenticated
 */
class ContentController extends Template
{

    protected function getPagination()
    {
        return $this->container->getParameter('social.pagination');
    }

    public function indexAction()
    {
        return $this->redirectRouteOk('wall_index', [
                    'wallNick' => $this->getUser()->getUsername(),
                    'wallFilter' => 'all'
        ]);
    }

    public function ajaxMoreAction($offset, $wallNick, $wallFilter)
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            throw new AccessDeniedException('U haxxor');
        }

        return $this->renderWall($wallNick, $wallFilter, 'TrismegisteSocialBundle:Content:index_more.html.twig', [], $offset);
    }

    protected function checkOwningRight(Content $post)
    {
        if (!$this->get('security.context')->isGranted('OWNER', $post)) {
            throw new AccessDeniedException('Unauthorised access!');
        }
    }

    public function wallAction($wallNick, $wallFilter)
    {
        return $this->renderWall($wallNick, $wallFilter, 'TrismegisteSocialBundle:Content:index.html.twig');
    }

    /**
     * The rendering of index.html.twig or its subclass
     * 
     * @param string $wallNick the nickname of the currently viewed wall
     * @param string $wallFilter the filter of the currently viewed wall
     * @param string $wallSubview the twig template
     * @param array $parameters other parameters
     * 
     * @return Response
     * 
     * @throws NotFoundHttpException
     */
    protected function renderWall($wallNick, $wallFilter, $wallSubview, array $parameters = [], $offset = 0)
    {
        // filling the wall user (logged user or not)
        if ($wallNick === $this->getUser()->getUsername()) {
            $parameters['wallNick'] = $wallNick;
            $parameters['wallUser'] = $this->getUser();
        } else {
            $repo = $this->get('social.netizen.repository');
            $user = $repo->findByNickname($wallNick);
            if (is_null($user)) {
                throw new NotFoundHttpException("$wallNick does not exists");
            }
            $parameters['wallUser'] = $user;
            $parameters['wallNick'] = $user->getUsername();
        }
        // filling the wall filter
        $parameters['wallFilter'] = $wallFilter;

        // filling feed entries and skipping one if in CRUD
        $repo = $this->getRepository();
        $it = $repo->findWallEntries($parameters['wallUser'], $wallFilter, $offset, $this->getPagination());

        // do we need to skip a record because it is currently edited ?
        if (array_key_exists('skipped_pub', $parameters)) {
            $it = new SkippableIterator($it, [$parameters['skipped_pub']]);
        }

        $parameters['listing'] = $it;
        $parameters['pagination'] = $this->getPagination();

        return $this->render($wallSubview, $parameters);
    }

}