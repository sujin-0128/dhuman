-- 관리자 메뉴 추가 SQL
INSERT INTO `es_adminMenu`
	SET
	`adminMenuNo` = 'godo00910'
	, `adminMenuType` = 'd'
	, `adminMenuProductCode` = 'godomall'
	, `adminMenuPlusCode` = null
	, `adminMenuCode` = 'discountBundle'
	, `adminMenuDepth` = 2
	, `adminMenuParentNo` = 'godo00051'
	, `adminMenuSort` = 90
	, `adminMenuName` = '결합 할인 상품'
	, `adminMenuUrl` = null
	, `adminMenuDisplayType` = 'y'
	, `adminMenuDisplayNo` = 'godo00000'
	, `adminMenuSettingType` = 'd'
	, `adminMenuEcKind` = 'a'
	, `adminMenuHideVersion` = ''
	, `regDt` = now();

    INSERT INTO `es_adminMenu`
	SET
	`adminMenuNo` = 'godo00920'
	, `adminMenuType` = 's'
	, `adminMenuProductCode` = 'godomall'
	, `adminMenuPlusCode` = null
	, `adminMenuCode` = 'discountBundle'
	, `adminMenuDepth` = 2
	, `adminMenuParentNo` = 'godo00384'
	, `adminMenuSort` = 90
	, `adminMenuName` = '결합 할인 상품'
	, `adminMenuUrl` = null
	, `adminMenuDisplayType` = 'y'
	, `adminMenuDisplayNo` = 'godo00000'
	, `adminMenuSettingType` = 'd'
	, `adminMenuEcKind` = 'a'
	, `adminMenuHideVersion` = ''
	, `regDt` = now();


    INSERT INTO `es_adminMenu`
	SET
	`adminMenuNo` = 'godo00911'
	, `adminMenuType` = 'd'
	, `adminMenuProductCode` = 'godomall'
	, `adminMenuPlusCode` = null
	, `adminMenuCode` = 'discountBundleGroupList'
	, `adminMenuDepth` = 3
	, `adminMenuParentNo` = 'godo00910'
	, `adminMenuSort` = 100
	, `adminMenuName` = '결합 할인 상품 관리'
	, `adminMenuUrl` = 'discount_bundle_group_list.php'
	, `adminMenuDisplayType` = 'y'
	, `adminMenuDisplayNo` = 'godo00000'
	, `adminMenuSettingType` = 'd'
	, `adminMenuEcKind` = 'a'
	, `adminMenuHideVersion` = ''
	, `regDt` = now();

    INSERT INTO `es_adminMenu`
	SET
	`adminMenuNo` = 'godo00921'
	, `adminMenuType` = 's'
	, `adminMenuProductCode` = 'godomall'
	, `adminMenuPlusCode` = null
	, `adminMenuCode` = 'discountBundleGroupList'
	, `adminMenuDepth` = 3
	, `adminMenuParentNo` = 'godo00920'
	, `adminMenuSort` = 100
	, `adminMenuName` = '결합 할인 상품 관리'
	, `adminMenuUrl` = 'discount_bundle_group_list.php'
	, `adminMenuDisplayType` = 'y'
	, `adminMenuDisplayNo` = 'godo00000'
	, `adminMenuSettingType` = 'd'
	, `adminMenuEcKind` = 'a'
	, `adminMenuHideVersion` = ''
	, `regDt` = now();

    

INSERT INTO `es_adminMenu`
	SET
	`adminMenuNo` = 'godo00912'
	, `adminMenuType` = 'd'
	, `adminMenuProductCode` = 'godomall'
	, `adminMenuPlusCode` = null
	, `adminMenuCode` = 'discountBundleGroupRegister'
	, `adminMenuDepth` = 3
	, `adminMenuParentNo` = 'godo00910'
	, `adminMenuSort` = 100
	, `adminMenuName` = '결합 할인 상품 그룹 등록'
	, `adminMenuUrl` = 'discount_bundle_group_register.php'
	, `adminMenuDisplayType` = 'n'
	, `adminMenuDisplayNo` = 'godo00000'
	, `adminMenuSettingType` = 'd'
	, `adminMenuEcKind` = 'a'
	, `adminMenuHideVersion` = ''
	, `regDt` = now();

    INSERT INTO `es_adminMenu`
	SET
	`adminMenuNo` = 'godo00913'
	, `adminMenuType` = 'd'
	, `adminMenuProductCode` = 'godomall'
	, `adminMenuPlusCode` = null
	, `adminMenuCode` = 'discountBundleGroupModify'
	, `adminMenuDepth` = 3
	, `adminMenuParentNo` = 'godo00910'
	, `adminMenuSort` = 100
	, `adminMenuName` = '결합 할인 상품 그룹 수정'
	, `adminMenuUrl` = 'discount_bundle_group_register.php'
	, `adminMenuDisplayType` = 'n'
	, `adminMenuDisplayNo` = 'godo00000'
	, `adminMenuSettingType` = 'd'
	, `adminMenuEcKind` = 'a'
	, `adminMenuHideVersion` = ''
	, `regDt` = now();

-- 결합 할인 상품 관련 테이블 SQL
CREATE TABLE dpx_discount_bundle_group (
    sno INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '일련번호',
    groupCd VARCHAR(32) NOT NULL COMMENT '그룹 코드',
    groupNm VARCHAR(100) NOT NULL COMMENT '그룹명',
    scmNo INT UNSIGNED NOT NULL DEFAULT 1 COMMENT '공급사 SNO (기본: 1)',    
    groupDescription VARCHAR(255) NULL COMMENT '그룹 설명',    
    showNoBundlePopup ENUM('y', 'n') NULL COMMENT '결합 실패 시 팝업 노출 여부 (y: 노출, n: 미노출)',
    showCartBtnForBundle ENUM('y', 'n') NULL COMMENT '장바구니 사용 여부 (y: 사용, n: 미사용)',
    preCartBundlePopup ENUM('y', 'n') NULL COMMENT '장바구니 담기 팝업 노출 여부 (y: 노출, n: 미노출)',
    mainBuyBanner VARCHAR(255) NULL COMMENT '메인상품 바로구매시 팝업 배너 코드',  
    mainCartBanner VARCHAR(255) NULL COMMENT '메인상품 장바구니시 팝업 배너 코드', 
    discountCartBanner VARCHAR(255) NULL COMMENT '혜택상품 장바구니시 팝업 배너 코드', 
    regDt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일',
    modDt DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    PRIMARY KEY (sno),
    UNIQUE KEY uq_groupCd (groupCd)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='결합 할인 상품 그룹 정보';



CREATE TABLE dpx_discount_bundle_group_goods (
    sno INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '일련번호',
    groupCd VARCHAR(32) NOT NULL COMMENT '연결된 그룹 코드',
    goodsNo INT UNSIGNED NOT NULL COMMENT '상품 번호 (es_goods 참조)',
    bundleType VARCHAR(32) NOT NULL COMMENT '결함 상품 구분(mian:메인상품(판매함), discount:할인상품(판매안함))',
    regDt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '등록일',
    modDt DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일',
    PRIMARY KEY (sno),
    UNIQUE KEY uq_group_goods (groupCd, goodsNo),
    CONSTRAINT fk_group_goods_groupCd FOREIGN KEY (groupCd)
        REFERENCES dpx_discount_bundle_group (groupCd)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='결합 할인 그룹별 상품 연결 테이블';