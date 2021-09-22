<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Customize\Controller;

use DateTime;
use Eccube\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Customize\Entity\Holiday;
use Customize\Entity\WeekHoliday;
use Customize\Repository\HolidayRepository;
use Customize\Repository\WeekHolidayRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class HolidayController extends AbstractController
{
    /**
     * @var HolidayRepository
     */
    protected $holidayRepository;

    /**
     * @var WeekHolidayRepository
     */
    protected $weekHolidayRepository;

    /**
     * OrderController constructor.
     *
     * @param HolidayRepository $holidayRepository
     * @param WeekHolidayRepository $weekHolidayRepository
     */
    public function __construct(
        HolidayRepository $holidayRepository,
        WeekHolidayRepository $weekHolidayRepository
    ) {
        $this->holidayRepository = $holidayRepository;
        $this->weekHolidayRepository = $weekHolidayRepository;
    }

    
    /**
     * @Route("/%eccube_admin_route%/week_holiday", name="admin_week_holiday") 
     * @Template("@CustomShipping/admin/calendar/week_holiday.twig")
     */
    public function week_holiday(Request $request)
    {
        if('POST' === $request->getMethod()){
            $requestIds = [];
            if($request->get('Sun') != NULL){
                $requestIds[] = $request->get('Sun');
            }
            if($request->get('Mon') != NULL ){
               $requestIds[] = $request->get('Mon');
            }
            if($request->get('Tue') != NULL ){
               $requestIds[] = $request->get('Tue');
            }
            if($request->get('Wed') != NULL ){
               $requestIds[] = $request->get('Wed');
            }
            if($request->get('Thu') != NULL ){
               $requestIds[] = $request->get('Thu');
            }
            if($request->get('Fri') != NULL ){
               $requestIds[] = $request->get('Fri');
            }
            if($request->get('Sat') != NULL ){
                $requestIds[] = $request->get('Sat');
            }
            // var_export($requestIds);die;
            $WeekHolidaysAll = $this->weekHolidayRepository->findAll();
            foreach($WeekHolidaysAll as $holiday){
                $holiday->setStatus(0);
            }
            foreach($requestIds as $id){
                $WeekHoliday = $this->weekHolidayRepository->findOneBy([
                    'yobi_id' => $id
                ]);
                $WeekHoliday->setStatus(1);
                $em = $this->getDoctrine()->getManager();
                $em->persist($WeekHoliday);
                $em->flush();
            }

        }
        $WeekHolidays = $this->weekHolidayRepository->findBy([
            'status' => 1
        ]);
        $statusOkids=[];
        foreach($WeekHolidays as $WeekHoliday){
            $statusOkids[] = $WeekHoliday->getYobiId();
        }
        return [
            'StatusOkIds' => $statusOkids
        ];
    }
    /**
     * @Route("/%eccube_admin_route%/holiday", name="admin_holiday") 
     * @Template("@CustomShipping/admin/calendar/holiday.twig")
     */
    public function holiday(Request $request)
    {       
        if('POST' === $request->getMethod()){
            $title = $request->get('title');
            $year = $request->get('year');
            $month = $request->get('month');
            $day = $request->get('day');

            if(isset($title) && $title != "" && isset($month) && $month != "" && isset($day) && $day != "" && isset($year) && $year != ""){

                $Holiday = new Holiday();
                $Holiday->setTitle($title);
                $Holiday->setMonth($month);
                $Holiday->setYear($year);
                $Holiday->setDay($day);
                $Holiday->setDelFlag(1);
                $em = $this->getDoctrine()->getManager();
                $em->persist($Holiday);
                $em->flush();
            }
            return $this->redirectToRoute('admin_holiday');

        }
        $LiveHolidays = $this->holidayRepository->findBy([
            'del_flag' => 1
        ]);

        return [
            'LiveHolidays' => $LiveHolidays
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/holiday/cancel/{id}", name="admin_cancel_holiday")
     */
    public function cancel_holiday(Request $request, Holiday $holiday)
    {      
        $holiday->setDelFlag(0);
        $em = $this->getDoctrine()->getManager();
        $em->persist($holiday);
        $em->flush();
        return $this->redirectToRoute('admin_holiday');
    }

    
}
