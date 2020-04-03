# $FreeBSD$

PORTNAME=	pfSense-pkg-migrate-voucher
PORTVERSION=	0.1
PORTREVISION=	1
CATEGORIES=	sysutils
MASTER_SITES=	# empty
DISTFILES=	# empty
EXTRACT_ONLY=	# empty

MAINTAINER=	ammann@integreat-app.de
COMMENT=	pfSense package migrate-voucher

LICENSE=	MIT

NO_BUILD=	yes
NO_MTREE=	yes

SUB_FILES=	pkg-install pkg-deinstall
SUB_LIST=	PORTNAME=${PORTNAME}

do-extract:
	${MKDIR} ${WRKSRC}

do-install:
	${MKDIR} ${STAGEDIR}${PREFIX}/pkg
	${INSTALL_DATA} -m 0644 ${FILESDIR}${PREFIX}/pkg/migrate-voucher.xml \
		${STAGEDIR}${PREFIX}/pkg

	${MKDIR} ${STAGEDIR}${PREFIX}/captiveportal/migrate-voucher
	${MKDIR} ${STAGEDIR}${PREFIX}/captiveportal/migrate-voucher/
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/captiveportal/migrate-voucher/migrate.php \
		${STAGEDIR}${PREFIX}/captiveportal/migrate-voucher/
	${INSTALL_DATA} ${FILESDIR}${PREFIX}/captiveportal/migrate-voucher/alternative_login.php \
		${STAGEDIR}${PREFIX}/captiveportal/migrate-voucher/

	${MKDIR} ${STAGEDIR}/etc/inc/voucher_migrate
	${INSTALL_DATA} ${FILESDIR}/etc/inc/voucher_migrate/voucher_migrate.inc \
		${STAGEDIR}/etc/inc/voucher_migrate

	${MKDIR} ${STAGEDIR}${DATADIR}
	${INSTALL_DATA} ${FILESDIR}${DATADIR}/info.xml \
		${STAGEDIR}${DATADIR}
	@${REINPLACE_CMD} -i '' -e "s|%%PKGVERSION%%|${PKGVERSION}|" \
		${STAGEDIR}${DATADIR}/info.xml

.include <bsd.port.mk>
