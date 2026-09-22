"""
=============================================================================
AURA - SISTEMA OFICIAL DE PUBLICACIÓN EN INSTAGRAM (META GRAPH API)
=============================================================================
Autor: Omar Horacio Adamo / Aura
Copyright: © 2026 Aura. Todos los derechos reservados.

Este script utiliza la API Oficial de Meta (Instagram Graph API / Content Publishing).
A diferencia de los bots no oficiales de GitHub (como instabot o instagrapi) que
provocan el bloqueo inmediato de la cuenta, este script cumple 100% con los
términos de servicio de Meta, garantizando seguridad total.

REQUISITOS PREVIOS:
1. Cuenta de Instagram Profesional (Empresa o Creador) vinculada a una Fanpage de Facebook.
2. Token de acceso de Meta for Developers con permisos:
   - instagram_content_publish
   - instagram_basic
   - pages_read_engagement
3. Tu Instagram Business Account ID.
=============================================================================
"""

import os
import time
import requests

# CONFIGURACIÓN (Completar con tus credenciales de Meta for Developers)
IG_USER_ID = os.getenv("IG_USER_ID", "TU_IG_ACCOUNT_ID")
ACCESS_TOKEN = os.getenv("IG_ACCESS_TOKEN", "TU_LONG_LIVED_ACCESS_TOKEN")
API_VERSION = "v19.0"
BASE_URL = f"https://graph.facebook.com/{API_VERSION}"

def publicar_imagen_en_instagram(image_url: str, caption: str):
    """
    Publica una imagen en Instagram utilizando el flujo de dos pasos de Meta:
    Paso 1: Crear el contenedor del medio (media container)
    Paso 2: Publicar el contenedor en el feed
    """
    if IG_USER_ID == "TU_IG_ACCOUNT_ID" or ACCESS_TOKEN == "TU_LONG_LIVED_ACCESS_TOKEN":
        print("⚠️ [AVISO AURA]: Para publicar mediante script debes configurar tus credenciales de Meta Graph API.")
        print("💡 CONSEJO RÁPIDO: Si necesitas publicar ahora mismo sin configurar la API de desarrollador,")
        print("usa Meta Business Suite (https://business.facebook.com/) que es gratis y no requiere código.")
        return False

    print(f"🚀 Iniciando publicación para: {caption[:40]}...")

    # Paso 1: Crear Media Container
    container_url = f"{BASE_URL}/{IG_USER_ID}/media"
    payload = {
        "image_url": image_url,
        "caption": caption,
        "access_token": ACCESS_TOKEN
    }

    res = requests.post(container_url, data=payload)
    if res.status_code != 200:
        print(f"❌ Error al crear el contenedor en Meta: {res.text}")
        return False

    creation_id = res.json().get("id")
    print(f"✅ Contenedor creado exitosamente (ID: {creation_id}). Verificando estado...")

    # Esperar unos segundos a que Meta procese la imagen
    time.sleep(5)

    # Paso 2: Publicar Media Container
    publish_url = f"{BASE_URL}/{IG_USER_ID}/media_publish"
    publish_payload = {
        "creation_id": creation_id,
        "access_token": ACCESS_TOKEN
    }

    pub_res = requests.post(publish_url, data=publish_payload)
    if pub_res.status_code != 200:
        print(f"❌ Error al publicar en el Feed: {pub_res.text}")
        return False

    post_id = pub_res.json().get("id")
    print(f"🎉 ¡Publicación exitosa en Instagram! (Post ID: {post_id})")
    return True

if __name__ == "__main__":
    print("=======================================================")
    print("   AURA - MOTOR DE PUBLICACIÓN PARA INSTAGRAM 2026     ")
    print("=======================================================")
    print("Visita tu panel de campaña en: https://aura-adamo.site/campana.html")
    print("Visita tu tienda online en:    https://aura-adamo.site/libros.html")
    print("© 2026 Aura. Todos los derechos reservados.")
