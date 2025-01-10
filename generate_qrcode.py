import qrcode
from qrcode.image.styledpil import StyledPilImage
from qrcode.image.styles.moduledrawers import RoundedModuleDrawer
from qrcode.image.styles.colormasks import RadialGradiantColorMask
from PIL import Image, ImageDraw, ImageFont

# La phrase à convertir
input_phrase = "http://172.31.100.229/attendance/attendance.php?room_id=3"
room_number = "3"  # Numéro de la salle

# Générer le QR Code
qr = qrcode.QRCode(
    version=1,  # Taille du QR Code
    error_correction=qrcode.constants.ERROR_CORRECT_H,  # Niveau de correction d'erreur élevé
    box_size=10,
    border=4,
)
qr.add_data(input_phrase)  # Ajouter les données
qr.make(fit=True)

# Créer une image du QR Code avec des options esthétiques
img = qr.make_image(
    image_factory=StyledPilImage,
    module_drawer=RoundedModuleDrawer(),  # Modules arrondis
    color_mask=RadialGradiantColorMask(),  # Dégradé radial
)

# Convertir en mode RGB pour pouvoir éditer l'image
img = img.convert("RGB")

# Ajouter un texte (numéro) au centre du QR Code
draw = ImageDraw.Draw(img)
font_size = int(img.size[0] * 0.15)  # Taille de la police (15% de la taille du QR Code)
try:
    # Charger une police personnalisée
    font = ImageFont.truetype("arial.ttf", font_size)
except IOError:
    # Utiliser une police par défaut si "arial.ttf" n'est pas trouvée
    font = ImageFont.load_default()

# Calculer la taille exacte du texte avec `font.getbbox()`
text_bbox = font.getbbox(room_number)
text_width = text_bbox[2] - text_bbox[0]
text_height = text_bbox[3] - text_bbox[1]

# Calculer la position pour centrer le cercle
image_center = (img.size[0] // 2, img.size[1] // 2)
circle_radius = max(text_width, text_height) // 2 + 20  # Rayon du cercle
circle_position = (
    image_center[0] - circle_radius,
    image_center[1] - circle_radius,
    image_center[0] + circle_radius,
    image_center[1] + circle_radius,
)

# Ajouter un cercle semi-transparent derrière le texte
draw.ellipse(circle_position, fill=(255, 255, 255, 255))  # Fond blanc opaque

# Ajouter un contour autour du cercle
draw.ellipse(
    (
        circle_position[0] + 3,
        circle_position[1] + 3,
        circle_position[2] - 3,
        circle_position[3] - 3,
    ),
    outline="blue",
    width=3,
)

# Recalculer la position pour centrer le texte par rapport au cercle
text_position = (
    image_center[0] - text_width // 2,
    image_center[1] - text_height // 2,
)

# Ajouter le texte au centre de l'image
draw.text(text_position, room_number, font=font, fill="blue")  # Texte en bleu

# Sauvegarder ou afficher l'image
img.show()  # Affiche le QR Code
img.save("qrcode_salle_3.png")  # Sauvegarde dans un fichier
