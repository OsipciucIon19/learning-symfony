<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\MicroPost;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\MicroPostRepository;
use App\Security\Voter\MicroPostVoter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MicroPostController extends AbstractController
{
  #[Route('/micro-post', name: 'app_micro_post')]
  public function index(MicroPostRepository $posts): Response
  {

    return $this->render('micro_post/index.html.twig', [
      'posts' => $posts->findAllWithComments(),
    ]);
  }

  #[Route('/micro-post/{post}', name: 'app_micro_post_show')]
  #[IsGranted(MicroPostVoter::VIEW, 'post')]
  public function showOne(MicroPost $post): Response
  {
    return $this->render('micro_post/show_one.html.twig', [
      'post' => $post
    ]);
  }

  #[Route('/micro-post/add', name: 'app_micro_post_add', priority: 2)]
  #[IsGranted('ROLE_WRITER')]
  public function add(Request $request, MicroPostRepository $posts): Response
  {
    $microPost = new MicroPost();
    $form = $this->createFormBuilder($microPost)
      ->add('title')
      ->add('text')
      ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $post = $form->getData();
      $post->setCreated(new \DateTime());
      $post->setAuthor($this->getUser());
      $posts->save($post, true);

      $this->addFlash('success', 'Your post was added');

      return $this->redirectToRoute('app_micro_post');
    }

    return $this->renderForm(
      'micro_post/add.html.twig',
      [
        'form' => $form
      ]
    );
  }

  #[Route('/micro-post/{post}/edit', name: 'app_micro_post_edit')]
  #[IsGranted(MicroPostVoter::EDIT, 'post')]
  public function edit(MicroPost $post, Request $request, MicroPostRepository $posts): Response
  {
    $form = $this->createFormBuilder($post)
      ->add('title')
      ->add('text')
      ->getForm();

    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $post = $form->getData();
      $posts->save($post, true);

      $this->addFlash('success', 'Your post was updated');

      return $this->redirectToRoute('app_micro_post');
    }

    return $this->renderForm(
      'micro_post/edit.html.twig',
      [
        'form' => $form,
        'post' => $post
      ]
    );
  }

  #[Route('/micro-post/{post}/comment', name: 'app_micro_post_comment')]
  public function addComment(MicroPost $post, Request $request, CommentRepository $comments): Response
  {
    $form = $this->createForm(CommentType::class, new Comment());
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $comment = $form->getData();
      $comment->setPost($post);
      $comment->setAuthor($this->getUser());
      $comments->save($comment, true);

      $this->addFlash('success', 'Your comment has been updated');

      return $this->redirectToRoute('app_micro_post_show',['post' => $post->getId()]);
    }

    return $this->renderForm(
      'micro_post/comment.html.twig',
      [
        'form' => $form,
        'post' => $post
      ]
    );
  }
}
