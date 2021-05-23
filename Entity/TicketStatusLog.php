<?php

namespace Webkul\UVDesk\CoreFrameworkBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * TicketStatusLog
 * @ORM\Entity(repositoryClass=null)
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="uv_ticket_status_log")
 */
class TicketStatusLog
{
    /**
     * @var integer
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    private $id;

    /**
     * @var \DateTime
     * @ORM\Column(type="datetime")
     */
    private $changedAt;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $oldStatus;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    private $newStatus;

    /**
     * @var \Webkul\UserBundle\Entity\User
     * @ORM\ManyToOne(targetEntity="Webkul\UVDesk\CoreFrameworkBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     */
    private $user;

    /**
     * @var \Webkul\CoreFrameworkBundle\Entity\Ticket
     * @ORM\ManyToOne(targetEntity="Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket")
     * @ORM\JoinColumn(name="ticket_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $ticket;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set changedAt
     *
     * @param \DateTime $changedAt
     * @return TicketStatusLog
     */
    public function setChangedAt($changedAt)
    {
        $this->changedAt = $changedAt;

        return $this;
    }

    /**
     * Get changedAt
     *
     * @return \DateTime 
     */
    public function getChangedAt()
    {
        return $this->changedAt;
    }

    /**
     * Set user
     *
     * @param \Webkul\UserBundle\Entity\User $user
     * @return TicketStatusLog
     */
    public function setUser(\Webkul\UVDesk\CoreFrameworkBundle\Entity\User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return \Webkul\UserBundle\Entity\User 
     */
    public function getUser()
    {
        return $this->user;
    }


    /**
     * Set ticket
     *
     * @param \Webkul\CoreFrameworkBundle\Entity\Ticket $Ticket
     * @return TicketStatusLog
     */
    public function setTicket(\Webkul\UVDesk\CoreFrameworkBundle\Entity\Ticket $ticket = null)
    {
        $this->ticket = $ticket;

        return $this;
    }

    /**
     * Get ticket
     *
     * @return \Webkul\CoreFrameworkBundle\Entity\ticket 
     */
    public function getTicket()
    {
        return $this->ticket;
    }

    /**
     * Set oldstatus
     *
     * @param string $oldStatus
     *
     * @return TicketStatusLog
     */
    public function setOldStatus($oldStatus)
    {
        $this->oldStatus = $oldStatus;

        return $this;
    }

    /**
     * Get oldStatus
     *
     * @return string
     */
    public function getOldStatus()
    {
        return $this->oldStatus;
    }

    /**
     * Set newstatus
     *
     * @param string $newStatus
     *
     * @return TicketStatusLog
     */
    public function setNewStatus($newStatus)
    {
        $this->newStatus = $newStatus;

        return $this;
    }

    /**
     * Get newStatus
     *
     * @return string
     */
    public function getNewStatus()
    {
        return $this->newStatus;
    }
}
