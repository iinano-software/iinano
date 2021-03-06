<?php

/*
 * iinano
 */

namespace Trismegiste\SocialBundle\Ticket;

/**
 * Ticket is a entrance ticket. Acquired with a EntranceFee or a Coupon
 * Conceptually, in an e-commerce, this is an order
 */
class Ticket implements EntranceAccess
{

    /** @var PurchaseChoice */
    protected $purchase;

    /** @var \DateTime */
    protected $purchasedAt;

    /** @var \DateTime */
    protected $expiredAt;

    /** var array */
    protected $info = []; // when associated with a transaction

    public function __construct(PurchaseChoice $purchaseSystem, \DateTime $now = null)
    {
        if (is_null($now)) {
            $now = new \DateTime();
        }

        $this->purchase = $purchaseSystem;
        $this->purchasedAt = clone $now;
        $this->expiredAt = clone $now;
        $this->expiredAt->modify($this->purchase->getDurationOffset());
    }

    /**
     * @inheritdoc
     */
    public function isValid(\DateTime $now = null)
    {
        if (is_null($now)) {
            $now = new \DateTime();
        }

        return $this->getExpiredAt()->getTimestamp() >= $now->getTimestamp();
    }

    /**
     * @inheritdoc
     */
    public function getPurchasedAt()
    {
        return $this->purchasedAt;
    }

    /**
     * @inheritdoc
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(\DateTime $newDate)
    {
        $this->expiredAt = $newDate;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->purchase->getTitle();
    }

    public function setTransactionInfo(array $info)
    {
        $this->info = $info;
    }

    public function getTransactionInfo()
    {
        return $this->info;
    }

}
