<?php

namespace Sylius\Component\Core\Model;

/**
 * Noticia
 */
class Noticia
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string $titulo
     */
    protected $titulo;

    /**
     * @var string $titulo
     */
    protected $contenido;

    /**
     * Permalink de la noticia.
     * Usada en la URL para acceder a ella.
     *
     * @var string
     */
    protected $slug;

    /**
     * Creation time.
     *
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * Last update time.
     *
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

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
     * Set the titulo.
     *
     * @param string $titulo
     * @return Newsletter
     */
    public function setTitulo($titulo)
    {
        $this->titulo = $titulo;
    }

    /**
     * Get titulo.
     *
     * @return string
     */
    public function getTitulo()
    {
        return $this->titulo;
    }

    /**
     * Set the contenido.
     *
     * @param string $contenido
     * @return Newsletter
     */
    public function setContenido($contenido)
    {
        $this->contenido = $contenido;
    }

    /**
     * Get contenido.
     *
     * @return string
     */
    public function getContenido()
    {
        return $this->contenido;
    }

    /**
     * Set the permalink.
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get permalink/slug.
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Get creation time.
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set creation time.
     *
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get the time of last update.
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set the time of last update.
     *
     * @param \DateTime $updatedAt
     */
    public function setUpdatedAt(\DateTime $updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
