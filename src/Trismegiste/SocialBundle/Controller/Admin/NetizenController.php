<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Controller\Admin;

use Trismegiste\SocialBundle\Controller\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * NetizenController is a controller for administrating Netizen
 */
class NetizenController extends Template
{

    /**
     * Gets the repository for netizen
     *
     * @return \Trismegiste\SocialBundle\Repository\NetizenRepositoryInterface
     */
    protected function getRepository()
    {
        return $this->get('social.netizen.repository');
    }

    public function listingAction(Request $req)
    {
        $it = [];

        if (!(empty($search = $req->query->get('search', '')))) {
            $repo = $this->getRepository();
            $it = $repo->search($search)->limit(5);
        }

        return $this->render('TrismegisteSocialBundle:Admin:Netizen/listing.html.twig', [
                    'listing' => $it
        ]);
    }

    public function promoteAction()
    {

    }

    public function blockAction()
    {

    }

    public function editAction($id)
    {
        $repo = $this->getRepository();
        $netizen = $repo->findByPk($id);
        $form = $this->createForm(new EntranceFeeType(), $netizen);

        $form->handleRequest($this->getRequest());
        if ($form->isValid()) {
            $newFee = $form->getData();
            try {
                $repo->persist($newFee);
                $this->pushFlash('notice', 'Entrance fee saved');

                // return to the same page
                $this->redirectRouteOk('entrancefee_edit');
            } catch (\MongoException $e) {
                $this->pushFlash('warning', 'Cannot save entrance fee');
            }
        }

        return $this->render('TrismegisteSocialBundle:Admin:fee_form.html.twig', [
                    'form' => $form->createView()
        ]);
    }

    public function showAction($id)
    {
        return $this->render('TrismegisteSocialBundle:Admin:Netizen/show.html.twig', [
                    'netizen' => $this->getRepository()->findByPk($id)
        ]);
    }

}
