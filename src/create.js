document.addEventListener("DOMContentLoaded", function() {
const canvas = document.getElementById("canvas")
let selectedItem = null
let selectedItems = new Set()
let selectMode = false
let canvasZoom = 1

function setCanvasZoom(percent) {
  canvasZoom = percent / 100
  canvas.style.transform = `scale(${canvasZoom})`
  document.getElementById("zoomValue").textContent = `${percent}%`
}

const tabButtons = document.querySelectorAll(".tab-btn")
const tabContents = document.querySelectorAll(".tab-content")

tabButtons.forEach((btn) => {
  btn.addEventListener("click", () => {
    const tabName = btn.dataset.tab

    tabButtons.forEach((b) => b.classList.remove("active"))
    tabContents.forEach((tc) => tc.classList.remove("active"))

    btn.classList.add("active")
    document.getElementById(tabName + "-tab").classList.add("active")
  })
})

document.getElementById("toggleSelectMode").onclick = () => {
  selectMode = !selectMode
  const btn = document.getElementById("toggleSelectMode")
  btn.classList.toggle("active")
  btn.innerHTML = selectMode ? "🖱️ Select Mode uit" : "🖱️ Select Mode aan"

  if (!selectMode) {
    deselectAllItems()
  }
}

const stickers = document.querySelectorAll(".sticker")
stickers.forEach((sticker) => {
  sticker.addEventListener("click", () => {
    let img = document.createElement("img")
    img.src = sticker.src
    img.classList.add("canvas-item")
    img.style.left = "100px"
    img.style.top = "100px"
    img.style.width = "120px"
    img.style.height = "120px"
    img.style.transform = "rotate(0deg) scaleX(1) scaleY(1)"
    img.style.objectFit = "contain"
    img.dataset.flipX = "1"
    img.dataset.flipY = "1"
    makeDraggable(img)
    canvas.appendChild(img)
  })
})

document.getElementById("addText").onclick = () => {
  let text = prompt("Welke tekst wil je toevoegen?")
  if (!text) return
  let div = document.createElement("div")
  div.innerText = text
  div.classList.add("canvas-item", "text-item")
  div.style.left = "150px"
  div.style.top = "150px"
  div.style.fontSize = "40px"
  div.style.lineHeight = "1.1"
  div.style.padding = "4px"
  div.style.display = "inline-block"
  div.style.transform = "rotate(0deg) scaleX(1) scaleY(1)"
  div.dataset.flipX = "1"
  div.dataset.flipY = "1"
  makeDraggable(div)
  canvas.appendChild(div)
}

document.getElementById("flipHorizontal").onclick = () => {
  const items = selectMode ? selectedItems : selectedItem ? [selectedItem] : []
  items.forEach((item) => {
    let flipX = item.dataset.flipX || "1"
    flipX = flipX === "1" ? "-1" : "1"
    item.dataset.flipX = flipX
    updateTransform(item)
  })
}

document.getElementById("flipVertical").onclick = () => {
  const items = selectMode ? selectedItems : selectedItem ? [selectedItem] : []
  items.forEach((item) => {
    let flipY = item.dataset.flipY || "1"
    flipY = flipY === "1" ? "-1" : "1"
    item.dataset.flipY = flipY
    updateTransform(item)
  })
}

document.getElementById("deleteSelected").onclick = () => {
  const items = selectMode ? Array.from(selectedItems) : selectedItem ? [selectedItem] : []
  items.forEach((item) => item.remove())
  deselectAllItems()
  updateToolButtons()
}

document.getElementById("sizeSlider").addEventListener("input", (e) => {
  const newSize = e.target.value
  document.getElementById("sizeValue").textContent = newSize + "px"

  const items = selectMode ? selectedItems : selectedItem ? [selectedItem] : []
  items.forEach((item) => {
    if (item.classList.contains("text-item")) {
      item.style.fontSize = newSize + "px"
    } else {
      item.style.width = newSize + "px"
      item.style.height = newSize + "px"
    }
  })
})

function applyTextStyleToSelected(callback) {
  const items = selectMode ? selectedItems : selectedItem ? [selectedItem] : []
  items.forEach((item) => {
    if (item.classList.contains("text-item")) {
      callback(item)
    }
  })
}

document.getElementById("textColor").addEventListener("input", (e) => {
  applyTextStyleToSelected((item) => (item.style.color = e.target.value))
})

document.getElementById("fontFamily").addEventListener("change", (e) => {
  applyTextStyleToSelected((item) => (item.style.fontFamily = e.target.value))
})

const zoomSlider = document.getElementById("zoomSlider")
zoomSlider.addEventListener("input", (e) => {
  setCanvasZoom(e.target.value)
})
setCanvasZoom(100)

document.getElementById("bgColor").addEventListener("input", (e) => {
  canvas.style.backgroundColor = e.target.value
})

document.getElementById("rulesToggle").addEventListener("click", () => {
  document.getElementById("rulesPanel").classList.toggle("open")
})

document.addEventListener("keydown", (e) => {
  if (e.key === "Delete" && selectedItem) {
    selectedItem.remove()
    selectedItem = null
    updateToolButtons()
  }
  if (e.key === "h" && selectedItem) {
    document.getElementById("flipHorizontal").click()
  }
  if (e.key === "v" && selectedItem) {
    document.getElementById("flipVertical").click()
  }
})

canvas.addEventListener("click", (e) => {
  if (e.target === canvas) {
    deselectItem()
  }
})

document.getElementById("savePNG").onclick = async () => {
  canvas.classList.add("exporting")
  const oldTransform = canvas.style.transform
  const downloadName = canvas.dataset.downloadName || "propaganda-poster.png"
  canvas.style.transform = "none"
  try {
    const exportedCanvas = await html2canvas(canvas, { backgroundColor: null, scale: 2, useCORS: true })
    const link = document.createElement("a")
    link.download = downloadName
    link.href = exportedCanvas.toDataURL("image/png")
    link.click()
  } catch (error) {
    alert("Fout bij exporteren: " + error.message)
  } finally {
    canvas.style.transform = oldTransform
    canvas.classList.remove("exporting")
  }
}

function updateToolButtons() {
  const hasSelection = selectedItem !== null || selectedItems.size > 0
  const isMultiSelect = selectedItems.size > 1
  const itemCount = Math.max(selectedItems.size, selectedItem ? 1 : 0)

  document.getElementById("flipHorizontal").disabled = !hasSelection
  document.getElementById("flipVertical").disabled = !hasSelection
  document.getElementById("deleteSelected").disabled = !hasSelection

  const label = document.getElementById("selectedItemsLabel")
  if (isMultiSelect) {
    label.textContent = `${itemCount} Items Geselecteerd`
  } else if (selectedItem) {
    label.textContent = "1 Item Geselecteerd"
  } else {
    label.textContent = "Geen items geselecteerd"
  }

  const sizeControls = document.getElementById("sizeControls")
  sizeControls.style.display = hasSelection ? "block" : "none"

  const textStyleControls = document.getElementById("textStyleControls")
  let selectedText = selectedItem && selectedItem.classList.contains("text-item")
  if (!selectedText && selectedItems.size === 1) {
    selectedText = Array.from(selectedItems)[0].classList.contains("text-item")
  }
  textStyleControls.style.display = selectedText ? "block" : "none"

  if (selectedItem && selectedItems.size === 0) {
    const sizeSlider = document.getElementById("sizeSlider")
    const sizeValue = document.getElementById("sizeValue")

    if (selectedItem.classList.contains("text-item")) {
      const size = parseInt(window.getComputedStyle(selectedItem).fontSize) || 40
      sizeSlider.min = 12
      sizeSlider.max = 120
      sizeSlider.value = Math.min(Math.max(size, 12), 120)
      sizeValue.textContent = sizeSlider.value + "px"
    } else {
      const size = selectedItem.offsetWidth || 120
      sizeSlider.min = 30
      sizeSlider.max = 500
      sizeSlider.value = Math.min(Math.max(size, 30), 500)
      sizeValue.textContent = sizeSlider.value + "px"
    }
  }
}

function selectItem(el, addToSelection = false) {
  if (selectMode) {
    if (addToSelection) {
      if (selectedItems.has(el)) {
        selectedItems.delete(el)
        el.classList.remove("selected-multi")
      } else {
        selectedItems.add(el)
        el.classList.add("selected-multi")
      }
    } else {
      deselectAllItems()
      selectedItems.add(el)
      el.classList.add("selected-multi")
    }
    selectedItem = null
    removeAllHandles()
  } else {
    deselectAllItems()
    selectedItem = el
    el.classList.add("selected")
    addHandles(el)
  }
  updateToolButtons()
}

function deselectAllItems() {
  selectedItems.forEach((item) => {
    item.classList.remove("selected-multi")
  })
  selectedItems.clear()

  if (selectedItem) {
    selectedItem.classList.remove("selected")
    removeHandles(selectedItem)
    selectedItem = null
  }
  updateToolButtons()
}

function deselectItem() {
  deselectAllItems()
}

function removeAllHandles() {
  const allItems = document.querySelectorAll(".canvas-item")
  allItems.forEach((item) => removeHandles(item))
}

function addHandles(el) {
  const oldHandles = el.querySelectorAll(".handle")
  oldHandles.forEach((h) => h.remove())

  let rotateHandle = document.createElement("div")
  rotateHandle.classList.add("handle", "rotate-handle")
  rotateHandle.title = "Draai voor rotatie"

  let resizeHandle = document.createElement("div")
  resizeHandle.classList.add("handle", "resize-handle")
  resizeHandle.title = "Sleep voor vergroten/verkleinen"

  let deleteBtn = document.createElement("div")
  deleteBtn.classList.add("handle", "delete-handle")
  deleteBtn.innerHTML = "×"
  deleteBtn.title = "Klik om te verwijderen"
  deleteBtn.addEventListener("click", (e) => {
    e.stopPropagation()
    el.remove()
    selectedItem = null
  })

  el.appendChild(rotateHandle)
  el.appendChild(resizeHandle)
  el.appendChild(deleteBtn)

  makeRotatable(el, rotateHandle)
  makeResizable(el, resizeHandle)
}

function removeHandles(el) {
  const handles = el.querySelectorAll(".handle")
  handles.forEach((h) => h.remove())
}

function updateTransform(el) {
  let rotation = getRotation(el)
  let flipX = el.dataset.flipX || "1"
  let flipY = el.dataset.flipY || "1"
  el.style.transform = `scaleX(${flipX}) scaleY(${flipY}) rotate(${rotation}deg)`
}

function makeDraggable(el) {
  el.addEventListener("click", (e) => {
    e.stopPropagation()
    selectItem(el, e.shiftKey)
  })

  let offsetX, offsetY

  el.onmousedown = function (e) {
    if (e.target.classList.contains("handle")) return

    const rect = canvas.getBoundingClientRect()
    const startX = (e.clientX - rect.left) / canvasZoom
    const startY = (e.clientY - rect.top) / canvasZoom

    const currentLeft = parseFloat(el.style.left) || parseFloat(window.getComputedStyle(el).left) || 0
    const currentTop = parseFloat(el.style.top) || parseFloat(window.getComputedStyle(el).top) || 0
    offsetX = startX - currentLeft
    offsetY = startY - currentTop

    document.onmousemove = function (e) {
      const moveX = (e.clientX - rect.left) / canvasZoom
      const moveY = (e.clientY - rect.top) / canvasZoom
      el.style.left = `${moveX - offsetX}px`
      el.style.top = `${moveY - offsetY}px`
    }

    document.onmouseup = function () {
      document.onmousemove = null
    }
  }
}

function makeRotatable(el, handle) {
  let startAngle = 0
  let currentAngle = 0

  handle.addEventListener("mousedown", (e) => {
    e.preventDefault()
    startAngle = e.clientY
    currentAngle = getRotation(el)

    document.onmousemove = function (e) {
      let diff = e.clientY - startAngle
      let newAngle = currentAngle + diff * 0.5

      let flipX = el.dataset.flipX || "1"
      let flipY = el.dataset.flipY || "1"
      el.style.transform = `scaleX(${flipX}) scaleY(${flipY}) rotate(${newAngle}deg)`
    }

    document.onmouseup = function () {
      document.onmousemove = null
    }
  })
}

function makeResizable(el, handle) {
  let startX, startY, startWidth, startHeight

  handle.addEventListener("mousedown", (e) => {
    e.preventDefault()
    startX = e.clientX
    startY = e.clientY
    startWidth = el.offsetWidth
    startHeight = el.offsetHeight

    document.onmousemove = function (e) {
      let newWidth = startWidth + (e.clientX - startX)
      let newHeight = startHeight + (e.clientY - startY)

      if (newWidth > 30) el.style.width = newWidth + "px"
      if (newHeight > 30) el.style.height = newHeight + "px"
    }

    document.onmouseup = function () {
      document.onmousemove = null
    }
  })
}

function getRotation(el) {
  let transform = el.style.transform
  let match = transform.match(/rotate\(([^)]+)deg\)/)
  return match ? parseFloat(match[1]) : 0
}
});
