<?php

/*
 * Iinano
 */

namespace Trismegiste\SocialBundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Trismegiste\SocialBundle\Security\Netizen;
use Trismegiste\SocialBundle\Config;

/**
 * TicketVoter is a voter to vote if a user has a valid entrance means such as :
 * - a valid ticket
 * - a free_pass role
 * - the app is free
 */
class TicketVoter implements VoterInterface
{

    const ROLE_FREEPASS = 'ROLE_FREEPASS';
    const SUPPORTED_ATTRIBUTE = 'VALID_ACCESS';

    protected $freeAccess;
    protected $role_hierarchy;

    /**
     * Ctor
     *
     * @param Config\ProviderInterface $cfg is a provider for the global dynamic config
     */
    public function __construct(array $roles, Config\ProviderInterface $cfg)
    {
        $this->role_hierarchy = $roles;
        $this->freeAccess = (bool) $cfg->read()['freeAccess'];
    }

    public function supportsAttribute($attribute)
    {
        return self::SUPPORTED_ATTRIBUTE === $attribute;
    }

    /**
     * @codeCoverageIgnore
     */
    public function supportsClass($fqcn)
    {

    }

    public function vote(TokenInterface $token, $object, array $attributes)
    {
        // check if the voter is used correct, only allow one attribute
        // this isn't a requirement, it's just one easy way for you to
        // design your voter
        if (1 !== count($attributes)) {
            throw new \InvalidArgumentException('Only one attribute is allowed for TicketVoter');
        }

        // set the attribute to check against
        $attribute = $attributes[0];
        // check if the given attribute is covered by this voter
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // get current logged in user
        $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof Netizen) {
            return VoterInterface::ACCESS_DENIED;
        }

        if (($this->freeAccess) || ($this->hasFreeAccess($user))) {
            return VoterInterface::ACCESS_GRANTED;
        }

        if ($user->hasValidTicket()) {
            return VoterInterface::ACCESS_GRANTED;
        }

        // if everything else fails:
        return VoterInterface::ACCESS_DENIED;
    }

    private function hasFreeAccess(Netizen $user)
    {
        $roleGroup = $user->getGroup();

        return (isset($this->role_hierarchy[$roleGroup]) &&
                in_array(self::ROLE_FREEPASS, $this->role_hierarchy[$roleGroup]));
    }

}
